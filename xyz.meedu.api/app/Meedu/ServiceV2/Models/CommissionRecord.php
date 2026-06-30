<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员提成流水(下单即计提,机构承担)。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;
use App\Meedu\Merchant\BelongsToMerchant;

class CommissionRecord extends Model
{
    use BelongsToMerchant;

    protected $table = 'commission_records';

    protected $fillable = [
        'salesperson_id', 'merchant_id', 'order_id',
        'base_amount', 'rate', 'amount',
        'record_type', 'ref_record_id', 'pay_status', 'paid_at',
        'clawback_status', 'remark',
    ];

    protected $casts = [
        'rate' => 'float',
        'base_amount' => 'integer',
        'amount' => 'integer',
        'record_type' => 'integer',
        'pay_status' => 'integer',
        'paid_at' => 'datetime',
    ];

    // 记录类型
    const TYPE_ACCRUE = 1;   // 正向计提
    const TYPE_CLAWBACK = 2; // 退款冲正(负向)

    // 支付状态
    const PAY_PENDING = 0; // 待机构支付
    const PAY_PAID = 1;    // 已支付
}
