<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员提成提现。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;

class SalespersonWithdrawal extends Model
{
    protected $table = 'salesperson_withdrawals';

    protected $fillable = [
        'salesperson_id', 'amount', 'status', 'alipay_account', 'audit_remark',
    ];

    protected $casts = [
        'amount' => 'integer',
        'status' => 'integer',
    ];

    const STATUS_PENDING = 0; // 待审核
    const STATUS_APPROVED = 1; // 通过
    const STATUS_REJECTED = 2; // 拒绝
    const STATUS_PAID = 3;     // 已打款
}
