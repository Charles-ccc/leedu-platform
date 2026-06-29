<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员↔客户绑定关系(永久,可改派)。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;

class SalespersonUserRelation extends Model
{
    protected $table = 'salesperson_user_relations';

    protected $fillable = [
        'salesperson_id', 'user_id', 'bound_at', 'is_active', 'reassigned_from',
    ];

    protected $casts = [
        'bound_at' => 'datetime',
        'is_active' => 'integer',
    ];
}
