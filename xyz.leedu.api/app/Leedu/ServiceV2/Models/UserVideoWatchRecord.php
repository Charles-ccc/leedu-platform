<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use App\Constant\TableConstant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserVideoWatchRecord extends Model
{
    use BelongsToMerchant;
    use SoftDeletes;

    protected $table = TableConstant::TABLE_USER_VIDEO_WATCH_RECORDS;

    protected $fillable = [
        'user_id', 'course_id', 'video_id', 'watch_seconds', 'watched_at',
    ];
}
