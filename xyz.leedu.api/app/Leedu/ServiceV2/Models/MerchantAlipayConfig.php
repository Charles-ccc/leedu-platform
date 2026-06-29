<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：机构支付宝收单配置。密钥/证书字段用 encrypted cast 加密存储。
 */

namespace App\Leedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;
use App\Leedu\Merchant\BelongsToMerchant;

class MerchantAlipayConfig extends Model
{
    use BelongsToMerchant;

    protected $table = 'merchant_alipay_configs';

    protected $fillable = [
        'merchant_id', 'app_id', 'seller_id',
        'app_private_key', 'alipay_public_key',
        'app_cert_public_key', 'alipay_root_cert',
        'zhima_enabled', 'is_enabled',
    ];

    protected $casts = [
        // 敏感密钥/证书加密存储
        'app_private_key' => 'encrypted',
        'alipay_public_key' => 'encrypted',
        'app_cert_public_key' => 'encrypted',
        'alipay_root_cert' => 'encrypted',
        'zhima_enabled' => 'integer',
        'is_enabled' => 'integer',
    ];

    protected $hidden = [
        // 接口默认不输出明文密钥
        'app_private_key', 'alipay_public_key',
        'app_cert_public_key', 'alipay_root_cert',
    ];
}
