<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseChapter extends Model
{
    use BelongsToMerchant;
    use HasFactory;

    protected $table = 'course_chapter';

    protected $fillable = [
        'course_id', 'title', 'sort',
    ];
}
