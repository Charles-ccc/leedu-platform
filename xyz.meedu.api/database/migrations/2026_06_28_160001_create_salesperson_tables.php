<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员拓客返点体系。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalespersonTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('salespeople')) {
            Schema::create('salespeople', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 50)->comment('姓名');
                $table->string('mobile', 30)->default('')->comment('手机号');
                $table->string('invite_code', 32)->default('')->comment('专属推广码');
                $table->string('alipay_account', 191)->default('')->comment('收提成支付宝账号');
                $table->integer('balance')->default(0)->comment('可提现提成余额(分)');
                $table->tinyInteger('status')->default(1)->comment('1在职 2离职');
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['invite_code'], 'uk_invite_code');
                $table->index(['status'], 'idx_status');
                $table->engine = 'InnoDB';
            });
        }

        if (!Schema::hasTable('salesperson_user_relations')) {
            Schema::create('salesperson_user_relations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('salesperson_id')->comment('业务员ID');
                $table->unsignedBigInteger('user_id')->comment('客户(学员)ID');
                $table->timestamp('bound_at')->nullable()->comment('绑定时间');
                $table->tinyInteger('is_active')->default(1)->comment('是否有效:改派时旧关系置0');
                $table->unsignedBigInteger('reassigned_from')->default(0)->comment('改派来源业务员ID');
                $table->timestamps();
                $table->index(['user_id', 'is_active'], 'idx_user_active');
                $table->index(['salesperson_id'], 'idx_salesperson');
                $table->engine = 'InnoDB';
            });
        }

        if (!Schema::hasTable('commission_records')) {
            Schema::create('commission_records', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('salesperson_id')->comment('业务员ID');
                $table->unsignedBigInteger('merchant_id')->comment('承担提成的机构ID');
                $table->unsignedBigInteger('order_id')->comment('订单ID');
                $table->integer('base_amount')->default(0)->comment('计提基数:订单成交额(分)');
                $table->decimal('rate', 5, 2)->default(0)->comment('提成比例(%)');
                $table->integer('amount')->default(0)->comment('提成金额(分,冲正为负)');
                $table->tinyInteger('record_type')->default(1)->comment('1正向计提 2退款冲正');
                $table->unsignedBigInteger('ref_record_id')->default(0)->comment('冲正时指向原计提记录');
                $table->tinyInteger('pay_status')->default(0)->comment('0待机构支付 1已支付');
                $table->timestamp('paid_at')->nullable()->comment('机构支付时间');
                $table->tinyInteger('clawback_status')->default(0)->comment('冲正处理:0待处理 1已追回 2已下期扣抵 3部分');
                $table->string('remark', 255)->default('')->comment('备注');
                $table->timestamps();
                $table->index(['salesperson_id'], 'idx_salesperson');
                $table->index(['merchant_id'], 'idx_merchant');
                $table->index(['order_id'], 'idx_order');
                $table->index(['pay_status'], 'idx_pay_status');
                $table->engine = 'InnoDB';
            });
        }

        if (!Schema::hasTable('salesperson_withdrawals')) {
            Schema::create('salesperson_withdrawals', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('salesperson_id')->comment('业务员ID');
                $table->integer('amount')->default(0)->comment('提现金额(分)');
                $table->tinyInteger('status')->default(0)->comment('0待审核 1通过 2拒绝 3已打款');
                $table->string('alipay_account', 191)->default('')->comment('收款支付宝');
                $table->string('audit_remark', 255)->default('')->comment('审核备注');
                $table->timestamps();
                $table->index(['salesperson_id'], 'idx_salesperson');
                $table->index(['status'], 'idx_status');
                $table->engine = 'InnoDB';
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('salesperson_withdrawals');
        Schema::dropIfExists('commission_records');
        Schema::dropIfExists('salesperson_user_relations');
        Schema::dropIfExists('salespeople');
    }
}
