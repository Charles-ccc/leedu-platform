<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Services\Course\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseChapter extends Base
{
    use BelongsToMerchant;
    use HasFactory;

    protected $table = 'course_chapter';

    protected $fillable = [
        'course_id', 'title', 'sort',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function videos()
    {
        return $this->hasMany(Video::class, 'chapter_id');
    }
}
