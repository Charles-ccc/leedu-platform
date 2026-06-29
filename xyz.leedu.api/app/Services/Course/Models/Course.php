<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Services\Course\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Base
{
    use BelongsToMerchant;
    use SoftDeletes, HasFactory;

    public const SHOW_YES = 1;
    public const SHOW_NO = -1;

    public const REC_YES = 1;
    public const REC_NO = 0;

    public const IS_FREE_YES = 1;
    public const IS_FREE_NO = 0;

    public const IS_VIP_FREE_YES = 1;
    public const IS_VIP_FREE_NO = 0;

    // 课程审核状态
    public const AUDIT_PENDING = 0;
    public const AUDIT_PASSED = 1;
    public const AUDIT_REJECTED = 2;

    protected $table = 'courses';

    protected $fillable = [
        'user_id', 'title', 'slug', 'thumb', 'charge',
        'short_description', 'original_desc', 'render_desc', 'seo_keywords',
        'seo_description', 'published_at', 'is_show', 'category_id',
        'is_rec', 'user_count', 'is_free', 'is_allow_comment', 'is_vip_free',
        'audit_status', 'audit_remark', 'submitted_at', 'audited_at', 'audited_admin_id',
        'installment_enabled', 'installment_periods', 'installment_cycle_days',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected static function booted()
    {
        // 机构(merchant_id>0)新建课程：默认待审核 + 隐藏，需平台审核通过才上架
        static::creating(function ($course) {
            $ctx = app(\App\Leedu\Merchant\MerchantContext::class);
            if ($ctx->hasMerchant()) {
                $course->audit_status = self::AUDIT_PENDING;
                $course->is_show = self::SHOW_NO;
                $course->submitted_at = now();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chapters()
    {
        return $this->hasMany(CourseChapter::class, 'course_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function videos()
    {
        return $this->hasMany(Video::class, 'course_id', 'id');
    }

    /**
     * 作用域：显示.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeShow($query)
    {
        return $query->where('is_show', self::SHOW_YES);
    }

    /**
     * 作用域：已过审(平台自营 merchant_id=0 免审)。
     */
    public function scopeAuditApproved($query)
    {
        return $query->where(function ($q) {
            $q->where('merchant_id', 0)->orWhere('audit_status', self::AUDIT_PASSED);
        });
    }

    /**
     * 作用域：客户端可见(展示中 且 已过审/平台课)。
     */
    public function scopeClientVisible($query)
    {
        return $query->where('is_show', self::SHOW_YES)->auditApproved();
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeRecommend($query)
    {
        return $query->where('is_rec', self::REC_YES);
    }


    /**
     * 作用域：不显示.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeNotShow($query)
    {
        return $query->where('is_show', self::SHOW_NO);
    }

    /**
     * 作用域：上线的视频.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * 作用域：关键词搜索.
     *
     * @param $query
     * @param string $keywords
     *
     * @return mixed
     */
    public function scopeKeywords($query, string $keywords)
    {
        $keywords && $query->where('title', 'like', "%{$keywords}%");

        return $query;
    }

    /**
     * 评论.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(CourseComment::class, 'course_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }
}
