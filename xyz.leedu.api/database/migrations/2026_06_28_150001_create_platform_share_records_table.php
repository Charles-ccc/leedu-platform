<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M4：平台分成分账记录(每期扣款成功后,平台从机构流水中分成)。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlatformShareRecordsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('platform_share_records')) {
            return;
        }
        Schema::create('platform_share_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('order_installment_id')->default(0)->comment('订单分期ID(哪一期)');
            $table->unsignedBigInteger('merchant_id')->comment('机构ID');
            $table->decimal('rate', 5, 2)->default(0)->comment('平台分成比例(%)');
            $table->integer('base_amount')->default(0)->comment('计提基数:该期实付(分)');
            $table->integer('amount')->default(0)->comment('平台分成金额(分)');
            $table->string('alipay_settle_no')->default('')->comment('支付宝分账明细号');
            $table->tinyInteger('status')->default(0)->comment('0待分账 1成功 2失败 3已回退');
            $table->string('remark', 255)->default('')->comment('备注');
            $table->timestamps();

            $table->index(['merchant_id'], 'idx_merchant_id');
            $table->index(['order_id'], 'idx_order_id');
            $table->index(['status'], 'idx_status');
            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_share_records');
    }
}
