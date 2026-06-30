<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：机构(商户)表。merchant_id=0 视为平台自营/全局，机构从 id>=1 开始。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('merchants')) {
            return;
        }

        Schema::create('merchants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('机构名称');
            $table->string('slug', 100)->default('')->comment('机构标识(主页用)');
            $table->string('logo')->default('')->comment('机构LOGO');
            $table->string('intro', 1000)->default('')->comment('机构简介');
            $table->string('contact_name', 50)->default('')->comment('联系人');
            $table->string('contact_mobile', 30)->default('')->comment('联系电话');
            $table->tinyInteger('status')->default(0)->comment('0待审核 1正常 2禁用 3已驳回');
            $table->string('audit_remark', 500)->default('')->comment('审核备注');
            $table->unsignedBigInteger('owner_admin_id')->default(0)->comment('机构主账号(administrators.id)');
            $table->decimal('platform_share_rate', 5, 2)->default(0)->comment('平台分成比例(%)');
            $table->decimal('salesperson_commission_rate', 5, 2)->default(0)->comment('业务员提成比例(%)');
            $table->unsignedBigInteger('referrer_salesperson_id')->default(0)->comment('拉它入驻的业务员ID');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status'], 'idx_status');
            $table->index(['slug'], 'idx_slug');
            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('merchants');
    }
}
