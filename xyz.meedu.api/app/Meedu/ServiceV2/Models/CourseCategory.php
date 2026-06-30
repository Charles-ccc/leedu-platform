<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Meedu\ServiceV2\Models;

use App\Meedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseCategory extends Model
{
    use BelongsToMerchant;
    use HasFactory;

    protected $table = 'course_categories';

    protected $fillable = [
        'sort', 'name', 'parent_id', 'parent_chain', 'is_show',
    ];
}
