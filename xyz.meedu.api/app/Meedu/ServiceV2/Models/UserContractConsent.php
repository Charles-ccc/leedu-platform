<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：下单合同阅读勾选存证(append-only)。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;

class UserContractConsent extends Model
{
    protected $table = 'user_contract_consents';

    protected $fillable = [
        'user_id', 'order_id', 'merchant_id',
        'agreement_type', 'agreement_version', 'agreement_hash',
        'consented_at', 'consent_ip', 'consent_ua',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
    ];
}
