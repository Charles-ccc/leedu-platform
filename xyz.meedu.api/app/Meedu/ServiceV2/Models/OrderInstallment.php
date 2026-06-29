<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：订单分期/扣款计划。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;
use App\Meedu\Merchant\BelongsToMerchant;

class OrderInstallment extends Model
{
    use BelongsToMerchant;

    protected $table = 'order_installments';

    protected $fillable = [
        'order_id', 'merchant_id', 'period_no', 'amount',
        'plan_charge_at', 'status', 'retry_count', 'alipay_trade_no', 'charged_at',
    ];

    protected $casts = [
        'plan_charge_at' => 'datetime',
        'charged_at' => 'datetime',
    ];

    // 扣款状态
    const STATUS_PENDING = 0; // 待扣
    const STATUS_CHARGED = 1; // 已扣
    const STATUS_FAILED = 2;  // 扣款失败
    const STATUS_REFUNDED = 3; // 已退款
}
