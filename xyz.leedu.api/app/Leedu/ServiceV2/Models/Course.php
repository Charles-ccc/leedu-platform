<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use BelongsToMerchant;
    use SoftDeletes, HasFactory;

    protected $table = 'courses';

    protected $fillable = [
        'user_id', 'title', 'slug', 'thumb', 'charge',
        'short_description', 'original_desc', 'render_desc', 'seo_keywords',
        'seo_description', 'published_at', 'is_show', 'category_id',
        'is_rec', 'user_count', 'is_free', 'is_allow_comment',
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
                $course->is_show = self::IS_SHOW_NO;
                $course->submitted_at = now();
            }
        });
    }

    // 课程审核状态
    const AUDIT_PENDING = 0;  // 待审核
    const AUDIT_PASSED = 1;   // 通过
    const AUDIT_REJECTED = 2; // 驳回

    const IS_SHOW_YES = 1;    // 展示
    const IS_SHOW_NO = -1;    // 隐藏

    /**
     * 已过审(或平台自营免审)。
     */
    public function scopeAuditApproved($query)
    {
        return $query->where(function ($q) {
            $q->where('merchant_id', 0)->orWhere('audit_status', self::AUDIT_PASSED);
        });
    }

    /**
     * 客户端可见：展示中 且 已过审/平台课。用于课程详情等需显式兜底的客户端入口。
     */
    public function scopeClientVisible($query)
    {
        return $query->where('is_show', self::IS_SHOW_YES)->auditApproved();
    }

    public function chapters()
    {
        return $this->hasMany(CourseChapter::class, 'course_id');
    }


    public function videos()
    {
        return $this->hasMany(CourseVideo::class, 'course_id', 'id');
    }
}
