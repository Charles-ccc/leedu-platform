<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：机构自助配置本机构支付宝收单。仅机构管理员可用。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Illuminate\Http\Request;
use App\Meedu\Merchant\MerchantContext;
use App\Meedu\ServiceV2\Models\MerchantAlipayConfig;

class MerchantAlipayController extends BaseController
{
    private function merchantId(): int
    {
        /** @var MerchantContext $ctx */
        $ctx = app(MerchantContext::class);
        if (!$ctx->hasMerchant()) {
            abort(403, '只有机构管理员可以配置本机构支付宝');
        }
        return (int)$ctx->getMerchantId();
    }

    public function show()
    {
        $mid = $this->merchantId();
        $cfg = MerchantAlipayConfig::query()->where('merchant_id', $mid)->first();

        // 密钥不回传明文，仅返回"是否已配置"
        return $this->successData([
            'app_id' => $cfg->app_id ?? '',
            'seller_id' => $cfg->seller_id ?? '',
            'zhima_enabled' => $cfg->zhima_enabled ?? 0,
            'is_enabled' => $cfg->is_enabled ?? 0,
            'has_app_private_key' => $cfg && $cfg->app_private_key ? 1 : 0,
            'has_alipay_public_key' => $cfg && $cfg->alipay_public_key ? 1 : 0,
            'has_app_cert_public_key' => $cfg && $cfg->app_cert_public_key ? 1 : 0,
            'has_alipay_root_cert' => $cfg && $cfg->alipay_root_cert ? 1 : 0,
        ]);
    }

    public function save(Request $request)
    {
        $mid = $this->merchantId();

        $data = $request->validate([
            'app_id' => ['required', 'string', 'max:191'],
            'seller_id' => ['nullable', 'string', 'max:191'],
            'zhima_enabled' => ['nullable', 'integer', 'in:0,1'],
            'is_enabled' => ['nullable', 'integer', 'in:0,1'],
            // 密钥/证书：留空表示不修改(保留原值)
            'app_private_key' => ['nullable', 'string'],
            'alipay_public_key' => ['nullable', 'string'],
            'app_cert_public_key' => ['nullable', 'string'],
            'alipay_root_cert' => ['nullable', 'string'],
        ]);

        $cfg = MerchantAlipayConfig::query()->firstOrNew(['merchant_id' => $mid]);
        $cfg->merchant_id = $mid;
        $cfg->app_id = $data['app_id'];
        $cfg->seller_id = $data['seller_id'] ?? '';
        $cfg->zhima_enabled = $data['zhima_enabled'] ?? 0;
        $cfg->is_enabled = $data['is_enabled'] ?? 0;

        foreach (['app_private_key', 'alipay_public_key', 'app_cert_public_key', 'alipay_root_cert'] as $k) {
            if (!empty($data[$k])) {
                $cfg->{$k} = $data[$k];
            }
        }
        $cfg->save();

        return $this->success();
    }
}
