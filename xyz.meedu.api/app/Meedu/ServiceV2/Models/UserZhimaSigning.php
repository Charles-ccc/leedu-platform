<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：芝麻先享 实名核身+代扣签约 当前有效态(用户×机构)。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;

class UserZhimaSigning extends Model
{
    protected $table = 'user_zhima_signings';

    protected $fillable = [
        'user_id', 'merchant_id', 'alipay_open_id',
        'real_name', 'cert_no_enc',
        'verify_status', 'verify_channel', 'verified_at',
        'agreement_no', 'sign_status', 'signed_at', 'expired_at',
    ];

    protected $casts = [
        'cert_no_enc' => 'encrypted',
        'verify_status' => 'integer',
        'sign_status' => 'integer',
        'verified_at' => 'datetime',
        'signed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected $hidden = ['cert_no_enc'];

    // 核身
    const VERIFY_PENDING = 0;
    const VERIFY_PASSED = 1;
    const VERIFY_FAILED = 2;

    // 签约
    const SIGN_NONE = 0;
    const SIGN_SIGNED = 1;
    const SIGN_UNSIGNED = 2;
    const SIGN_EXPIRED = 3;
}
