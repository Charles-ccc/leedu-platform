<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：按机构解析支付宝(yansongda)配置。
 * - merchant_id<=0：平台自营，沿用平台配置(config/pay.php)。
 * - merchant_id>0：用该机构 merchant_alipay_configs 中的密钥/证书构建配置。
 * 返回结构与 ConfigService::getAlipayConfig() 一致，可直接传给 Pay::alipay()。
 */

namespace App\Meedu\Merchant;

use Illuminate\Support\Str;
use App\Meedu\ServiceV2\Models\MerchantAlipayConfig;
use App\Exceptions\ServiceException;
use App\Meedu\ServiceV2\Services\ConfigServiceInterface;

class AlipayClientResolver
{
    /**
     * 返回某机构可用的支付宝配置(yansongda 格式)。
     */
    public function configForMerchant(int $merchantId): array
    {
        // 平台自营 → 平台配置
        if ($merchantId <= 0) {
            return app(ConfigServiceInterface::class)->getAlipayConfig();
        }

        /** @var MerchantAlipayConfig $cfg */
        $cfg = MerchantAlipayConfig::withoutGlobalScopes()
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$cfg || !$cfg->is_enabled) {
            throw new ServiceException(__('该机构未配置或未启用支付宝收单'));
        }
        if (!$cfg->app_id || !$cfg->app_private_key || !$cfg->app_cert_public_key || !$cfg->alipay_root_cert) {
            throw new ServiceException(__('该机构支付宝配置不完整'));
        }

        $data = [
            'app_id' => $cfg->app_id,
            'notify_url' => route('payment.callback', ['alipay']),
            'return_url' => config('pay.alipay.return_url', ''),
            'private_key' => $cfg->app_private_key,
            'ali_public_key' => $this->writeCert('ali_public_key', $merchantId, $cfg->alipay_public_key),
            'app_cert_public_key' => $this->writeCert('app_cert', $merchantId, $cfg->app_cert_public_key),
            'alipay_root_cert' => $this->writeCert('root_cert', $merchantId, $cfg->alipay_root_cert),
            'log' => [
                'file' => storage_path('logs/alipay.log'),
                'level' => 'debug',
                'type' => 'single',
                'max_file' => 30,
            ],
        ];

        return $data;
    }

    /**
     * 把证书内容落地为文件(yansongda 证书参数需要路径)，按机构隔离缓存。
     */
    private function writeCert(string $type, int $merchantId, ?string $content): string
    {
        $content = (string)$content;
        $hash = md5($content);
        $path = storage_path("private/merchant_{$merchantId}_{$type}_{$hash}.crt");
        if (!is_file($path)) {
            file_put_contents($path, $content);
        }
        return $path;
    }
}
