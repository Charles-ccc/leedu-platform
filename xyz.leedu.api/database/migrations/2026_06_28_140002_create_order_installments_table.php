<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：订单分期/扣款计划。一次性付款=1期，芝麻先享=多期。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderInstallmentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('order_installments')) {
            return;
        }
        Schema::create('order_installments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('merchant_id')->default(0)->comment('所属机构ID');
            $table->integer('period_no')->default(1)->comment('第几期(一次性=1)');
            $table->integer('amount')->default(0)->comment('本期应扣金额(分)');
            $table->timestamp('plan_charge_at')->nullable()->comment('计划扣款时间');
            $table->tinyInteger('status')->default(0)->comment('0待扣 1已扣 2扣款失败 3已退款');
            $table->integer('retry_count')->default(0)->comment('催扣次数');
            $table->string('alipay_trade_no')->default('')->comment('支付宝交易号');
            $table->timestamp('charged_at')->nullable()->comment('实际扣款时间');
            $table->timestamps();

            $table->index(['order_id'], 'idx_order_id');
            $table->index(['merchant_id'], 'idx_merchant_id');
            $table->index(['status', 'plan_charge_at'], 'idx_status_plan');
            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_installments');
    }
}
