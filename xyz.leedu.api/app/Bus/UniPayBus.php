<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Bus;

use Yansongda\Pay\Pay;
use Illuminate\Support\Str;
use App\Constant\BusConstant;
use App\Leedu\Tencent\WechatMp;
use Illuminate\Support\Facades\Cache;
use App\Leedu\ServiceV2\Services\OrderServiceInterface;
use App\Leedu\ServiceV2\Services\ConfigServiceInterface;

class UniPayBus
{
    public const EXPIRE = 1800;

    public function generateSign(int $orderId): string
    {
        $sign = 'order-' . Str::random(24);
        if (!Cache::put($sign, $orderId, self::EXPIRE)) {
            throw new \Exception(__('缓存写入失败'));
        }
        return $sign;
    }

    public function getOrderBySign(string $sign): int
    {
        return (int)Cache::get($sign);
    }

    public function calculateActualPaymentAmount(array $order): array
    {
        /**
         * @var OrderServiceInterface $orderService
         */
        $orderService = app()->make(OrderServiceInterface::class);
        $orderPaidRecords = $orderService->getOrderPaidRecordsById($order['id']);
        $promoCodePaidRecord = [];
        foreach ($orderPaidRecords as $tmpItem) {
            if (BusConstant::ORDER_PAID_TYPE_PROMO_CODE === $tmpItem['paid_type']) {
                $promoCodePaidRecord = $tmpItem;
                break;
            }
        }
        $total = $order['charge'] - ($promoCodePaidRecord ? $promoCodePaidRecord['paid_total'] : 0);
        return compact('total', 'promoCodePaidRecord');
    }

    public function getWechatOpenid(): string
    {
        $openid = session('wechat_jsapi_openid');
        if ($openid) {
            return $openid;
        }
        if (request()->has('leedu_scene') && 'callback' === request()->input('leedu_scene')) {
            /**
             * @var WechatMp $wechatMp
             */
            $wechatMp = app()->make(WechatMp::class);
            $mpAccessToken = $wechatMp->getAccessToken(request()->input('code'));
            $openid = $mpAccessToken['openid'];
            // 缓存-避免重复的授权登录
            session(['wechat_jsapi_openid' => $openid]);
            return $openid;
        }
        return '';
    }

    public function redirectWechatOAuth(string $url)
    {
        /**
         * @var WechatMp $wechatMp
         */
        $wechatMp = app()->make(WechatMp::class);
        $redirect = url_append_query($url, ['leedu_scene' => 'callback']);
        return redirect($wechatMp->getBaseAuthUrl($redirect));
    }

    public function createWechatH5OrderWithCache(string $outTradeNo, string $totalFee, string $body): string
    {
        $cacheKey = sprintf('order-pay-%s', $outTradeNo);
        return Cache::get($cacheKey, function () use ($outTradeNo, $totalFee, $body) {
            /**
             * @var ConfigServiceInterface $configService
             */
            $configService = app()->make(ConfigServiceInterface::class);

            return Pay::wechat($configService->getWechatPayConfig())->wap([
                'out_trade_no' => $outTradeNo,
                'body' => $body,
                'total_fee' => $totalFee,
            ])->getContent();
        });
    }

    public function createWechatJSAPIOrderWithCache(string $outTradeNo, string $totalFee, string $body, string $openid): array
    {
        $cacheKey = sprintf('order-pay-%s', $outTradeNo);
        return Cache::get($cacheKey, function () use ($outTradeNo, $totalFee, $body, $openid) {
            /**
             * @var ConfigServiceInterface $configService
             */
            $configService = app()->make(ConfigServiceInterface::class);

            $data = Pay::wechat($configService->getWechatPayConfig())->mp([
                'out_trade_no' => $outTradeNo,
                'body' => $body,
                'total_fee' => $totalFee,
                'openid' => $openid,
            ]);

            return $data->toArray();
        });
    }

    public function createAlipayH5OrderWithCache(string $outTradeNo, string $totalFee, string $subject, string $returnUrl, int $merchantId = 0): string
    {
        $cacheKey = sprintf('order-pay-%s', $outTradeNo);
        return Cache::get($cacheKey, function () use ($outTradeNo, $totalFee, $subject, $returnUrl, $merchantId) {
            // 平台化:按订单所属机构解析支付宝配置(merchant_id=0 回退平台配置)
            $config = app()->make(\App\Leedu\Merchant\AlipayClientResolver::class)->configForMerchant($merchantId);
            // 重写支付成功的返回地址
            $config['return_url'] = $returnUrl;

            return Pay::alipay($config)->wap([
                'out_trade_no' => $outTradeNo,
                'total_amount' => $totalFee,
                'subject' => $subject,
            ])->getContent();
        });
    }
}
