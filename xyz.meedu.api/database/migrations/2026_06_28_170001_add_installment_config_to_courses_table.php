<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：课程分期(芝麻先享)配置。机构上架课程时设置。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInstallmentConfigToCoursesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('courses', 'installment_enabled')) {
            return;
        }
        Schema::table('courses', function (Blueprint $table) {
            $table->tinyInteger('installment_enabled')->default(0)->comment('是否支持芝麻先享分期:0否1是');
            $table->integer('installment_periods')->default(1)->comment('分期期数');
            $table->integer('installment_cycle_days')->default(30)->comment('每期周期(天)');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('courses', 'installment_enabled')) {
            return;
        }
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['installment_enabled', 'installment_periods', 'installment_cycle_days']);
        });
    }
}
