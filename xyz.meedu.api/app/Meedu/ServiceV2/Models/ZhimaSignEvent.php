<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：签约成功回调存证(append-only,合规举证)。
 */

namespace App\Meedu\ServiceV2\Models;

use Illuminate\Database\Eloquent\Model;

class ZhimaSignEvent extends Model
{
    protected $table = 'zhima_sign_events';

    protected $fillable = [
        'user_id', 'alipay_open_id', 'merchant_id',
        'agreement_no', 'agreement_version', 'agreement_hash',
        'signed_at', 'sign_ip', 'sign_ua', 'raw_callback',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];
}
