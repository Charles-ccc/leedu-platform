<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M4：平台分成分账记录。
 */

namespace App\Leedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;
use App\Leedu\Merchant\BelongsToMerchant;

class PlatformShareRecord extends Model
{
    use BelongsToMerchant;

    protected $table = 'platform_share_records';

    protected $fillable = [
        'order_id', 'order_installment_id', 'merchant_id',
        'rate', 'base_amount', 'amount', 'alipay_settle_no', 'status', 'remark',
    ];

    protected $casts = [
        'rate' => 'float',
        'base_amount' => 'integer',
        'amount' => 'integer',
        'status' => 'integer',
    ];

    // 分账状态
    const STATUS_PENDING = 0; // 待分账
    const STATUS_SUCCESS = 1; // 成功
    const STATUS_FAILED = 2;  // 失败
    const STATUS_REVERTED = 3; // 已回退(退款)
}
