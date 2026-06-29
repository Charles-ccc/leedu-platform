<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：核心业务表加 merchant_id。
 * 默认 0 = 平台自营/全局，现有数据自动归平台自营，无需回填。
 * 机构数据 merchant_id = merchants.id (>=1)。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMerchantIdToBusinessTables extends Migration
{
    /**
     * 需要按机构隔离的核心业务表（内容/交易/学习关系/媒资）。
     * 用户级表(users/user_profiles 等)保持平台级，不加。
     */
    private $tables = [
        // 内容/商品
        'courses', 'videos',
        'course_categories', 'course_chapter',
        'course_comments', 'video_comments',
        'course_attach', 'course_attach_download_records',
        // 媒资库
        'media_images', 'media_videos', 'media_video_categories',
        // 学习/购买关系
        'user_course', 'user_video', 'course_user_records', 'user_video_watch_records',
        // 交易
        'orders', 'order_goods', 'order_paid_records', 'order_refund',
        'promo_codes',
    ];

    public function up()
    {
        foreach ($this->tables as $name) {
            if (!Schema::hasTable($name) || Schema::hasColumn($name, 'merchant_id')) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_id')->default(0)
                    ->comment('所属机构ID，0=平台自营');
                $table->index(['merchant_id'], 'idx_merchant_id');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $name) {
            if (!Schema::hasTable($name) || !Schema::hasColumn($name, 'merchant_id')) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex('idx_merchant_id');
                $table->dropColumn('merchant_id');
            });
        }
    }
}
