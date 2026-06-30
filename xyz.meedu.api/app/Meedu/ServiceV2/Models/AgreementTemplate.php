<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：协议模板(网课买卖合同/芝麻代扣合同),按类型+版本管理。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementTemplate extends Model
{
    protected $table = 'agreement_templates';

    protected $fillable = [
        'type', 'merchant_id', 'version', 'title', 'content', 'content_hash',
        'is_active', 'effective_at',
    ];

    protected $casts = [
        'merchant_id' => 'integer',
        'is_active' => 'integer',
        'effective_at' => 'datetime',
    ];

    const TYPE_COURSE_SALE = 'course_sale';     // 网课买卖合同
    const TYPE_ZHIMA_WITHHOLD = 'zhima_withhold'; // 芝麻代扣合同
}
