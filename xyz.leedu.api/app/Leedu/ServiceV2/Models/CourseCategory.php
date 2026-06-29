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

class CourseCategory extends Model
{
    use BelongsToMerchant;
    use HasFactory;

    protected $table = 'course_categories';

    protected $fillable = [
        'sort', 'name', 'parent_id', 'parent_chain', 'is_show',
    ];
}
