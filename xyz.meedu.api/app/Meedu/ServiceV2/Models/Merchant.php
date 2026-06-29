<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：机构(商户)模型。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use SoftDeletes;

    protected $table = 'merchants';

    protected $fillable = [
        'name', 'slug', 'logo', 'intro',
        'contact_name', 'contact_mobile',
        'status', 'audit_remark', 'owner_admin_id',
        'platform_share_rate', 'salesperson_commission_rate',
        'referrer_salesperson_id',
    ];

    protected $casts = [
        'status' => 'integer',
        'owner_admin_id' => 'integer',
        'platform_share_rate' => 'float',
        'salesperson_commission_rate' => 'float',
        'referrer_salesperson_id' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // 机构状态
    const STATUS_PENDING = 0;  // 待审核
    const STATUS_NORMAL = 1;   // 正常
    const STATUS_BANNED = 2;   // 禁用
    const STATUS_REJECTED = 3; // 已驳回

    public function admins()
    {
        return $this->hasMany(\App\Models\Administrator::class, 'merchant_id');
    }
}
