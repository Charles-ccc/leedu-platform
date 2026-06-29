<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员(平台级)。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salesperson extends Model
{
    use SoftDeletes;

    protected $table = 'salespeople';

    protected $fillable = [
        'name', 'mobile', 'invite_code', 'alipay_account', 'balance', 'status',
    ];

    protected $casts = [
        'balance' => 'integer',
        'status' => 'integer',
    ];

    const STATUS_ACTIVE = 1; // 在职
    const STATUS_LEFT = 2;   // 离职
}
