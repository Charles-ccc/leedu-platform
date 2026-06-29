<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：芝麻先享 - 协议模板、合同同意存证、实名核身+代扣签约。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateZhimaTables extends Migration
{
    public function up()
    {
        // 协议模板(网课买卖合同/芝麻代扣合同),按类型+版本管理
        if (!Schema::hasTable('agreement_templates')) {
            Schema::create('agreement_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('type', 30)->comment('course_sale网课买卖 / zhima_withhold芝麻代扣');
                $table->unsignedBigInteger('merchant_id')->default(0)->comment('0平台通用,>0机构自定义');
                $table->string('version', 50)->comment('协议版本号');
                $table->string('title')->default('')->comment('协议标题');
                $table->longText('content')->comment('协议文本');
                $table->string('content_hash', 64)->default('')->comment('文本MD5/Hash');
                $table->tinyInteger('is_active')->default(1)->comment('是否当前生效版本');
                $table->timestamp('effective_at')->nullable()->comment('生效时间');
                $table->timestamps();
                $table->index(['type', 'merchant_id', 'is_active'], 'idx_type_merchant_active');
                $table->engine = 'InnoDB';
            });
        }

        // 下单合同阅读勾选存证(append-only)
        if (!Schema::hasTable('user_contract_consents')) {
            Schema::create('user_contract_consents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->comment('用户ID');
                $table->unsignedBigInteger('order_id')->default(0)->comment('订单ID');
                $table->unsignedBigInteger('merchant_id')->default(0)->comment('机构ID');
                $table->string('agreement_type', 30)->comment('合同类型');
                $table->string('agreement_version', 50)->default('')->comment('合同版本');
                $table->string('agreement_hash', 64)->default('')->comment('合同文本Hash');
                $table->timestamp('consented_at')->nullable()->comment('勾选同意时间');
                $table->string('consent_ip', 64)->default('')->comment('IP');
                $table->text('consent_ua')->nullable()->comment('User-Agent');
                $table->timestamps();
                $table->index(['user_id'], 'idx_user');
                $table->index(['order_id'], 'idx_order');
                $table->engine = 'InnoDB';
            });
        }

        // 芝麻先享 实名核身+代扣签约:当前有效态(归属 用户×机构)
        if (!Schema::hasTable('user_zhima_signings')) {
            Schema::create('user_zhima_signings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->comment('用户ID');
                $table->unsignedBigInteger('merchant_id')->comment('机构ID(签约绑定该机构支付宝应用)');
                $table->string('alipay_open_id')->default('')->comment('支付宝open_id');
                $table->string('real_name')->default('')->comment('实名姓名');
                $table->text('cert_no_enc')->nullable()->comment('证件号(加密)');
                $table->tinyInteger('verify_status')->default(0)->comment('核身:0未核 1通过 2失败');
                $table->string('verify_channel', 50)->default('')->comment('核身方式');
                $table->timestamp('verified_at')->nullable();
                $table->string('agreement_no')->default('')->comment('当前有效代扣签约协议号');
                $table->tinyInteger('sign_status')->default(0)->comment('签约:0未签 1已签 2已解约 3已失效');
                $table->timestamp('signed_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'merchant_id'], 'uk_user_merchant');
                $table->engine = 'InnoDB';
            });
        }

        // 签约成功回调存证(append-only,合规举证)
        if (!Schema::hasTable('zhima_sign_events')) {
            Schema::create('zhima_sign_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->comment('用户ID');
                $table->string('alipay_open_id')->default('')->comment('支付宝open_id');
                $table->unsignedBigInteger('merchant_id')->comment('机构ID');
                $table->string('agreement_no')->default('')->comment('本次签约协议号');
                $table->string('agreement_version', 50)->default('')->comment('协议版本号');
                $table->string('agreement_hash', 64)->default('')->comment('协议文本Hash');
                $table->timestamp('signed_at')->nullable()->comment('签约时间戳');
                $table->string('sign_ip', 64)->default('')->comment('IP');
                $table->text('sign_ua')->nullable()->comment('User-Agent');
                $table->longText('raw_callback')->nullable()->comment('支付宝原始回调报文');
                $table->timestamps();
                $table->index(['user_id'], 'idx_user');
                $table->index(['merchant_id'], 'idx_merchant');
                $table->engine = 'InnoDB';
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('zhima_sign_events');
        Schema::dropIfExists('user_zhima_signings');
        Schema::dropIfExists('user_contract_consents');
        Schema::dropIfExists('agreement_templates');
    }
}
