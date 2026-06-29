<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M2：课程审核字段。
 * audit_status 默认 0(待审核)；客户端展示规则为「平台课(merchant_id=0)免审 或 audit_status=1」，
 * 因此现有平台数据无需回填即可正常展示。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAuditColumnsToCoursesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('courses', 'audit_status')) {
            return;
        }
        Schema::table('courses', function (Blueprint $table) {
            $table->tinyInteger('audit_status')->default(0)->comment('审核状态:0待审核 1通过 2驳回');
            $table->string('audit_remark', 500)->default('')->comment('审核备注');
            $table->timestamp('submitted_at')->nullable()->comment('提交审核时间');
            $table->timestamp('audited_at')->nullable()->comment('审核时间');
            $table->unsignedBigInteger('audited_admin_id')->default(0)->comment('审核人(平台管理员ID)');
            $table->index(['audit_status'], 'idx_audit_status');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('courses', 'audit_status')) {
            return;
        }
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_audit_status');
            $table->dropColumn(['audit_status', 'audit_remark', 'submitted_at', 'audited_at', 'audited_admin_id']);
        });
    }
}
