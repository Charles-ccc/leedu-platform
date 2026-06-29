<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Services\Course\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use App\Constant\TableConstant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseComment extends Base
{
    use BelongsToMerchant;
    use SoftDeletes, HasFactory;

    protected $table = TableConstant::TABLE_COURSE_COMMENTS;

    protected $fillable = [
        'user_id', 'course_id', 'original_content', 'render_content', 'ip', 'ip_province', 'is_check',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
