<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：机构支付宝收单配置(每机构独立商户号/密钥)。
 * 密钥字段由模型用 encrypted cast 加密存储。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantAlipayConfigsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('merchant_alipay_configs')) {
            return;
        }
        Schema::create('merchant_alipay_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('merchant_id')->comment('所属机构ID');
            $table->string('app_id')->default('')->comment('支付宝应用APPID');
            $table->string('seller_id')->default('')->comment('支付宝商户号/PID');
            // 以下密钥/证书字段加密存储(密文较长,用 text)
            $table->text('app_private_key')->nullable()->comment('应用私钥(加密)');
            $table->text('alipay_public_key')->nullable()->comment('支付宝公钥(加密)');
            $table->text('app_cert_public_key')->nullable()->comment('应用公钥证书(加密)');
            $table->text('alipay_root_cert')->nullable()->comment('支付宝根证书(加密)');
            $table->tinyInteger('zhima_enabled')->default(0)->comment('是否开通芝麻先享:0否1是');
            $table->tinyInteger('is_enabled')->default(0)->comment('是否启用:0否1是');
            $table->timestamps();

            $table->unique(['merchant_id'], 'uk_merchant_id');
            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('merchant_alipay_configs');
    }
}
