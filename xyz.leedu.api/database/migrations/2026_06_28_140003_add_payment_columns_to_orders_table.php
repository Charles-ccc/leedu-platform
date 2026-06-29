<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：订单支付相关字段。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentColumnsToOrdersTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('orders', 'pay_type')) {
            return;
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pay_type', 20)->default('onetime')->comment('付款方式:onetime一次性 zhima芝麻先享');
            $table->integer('total_periods')->default(1)->comment('总期数(一次性=1)');
            $table->unsignedBigInteger('bound_salesperson_id')->default(0)->comment('下单时绑定的业务员ID');
            $table->string('zhima_agreement_no')->default('')->comment('芝麻先享代扣签约协议号');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('orders', 'pay_type')) {
            return;
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pay_type', 'total_periods', 'bound_salesperson_id', 'zhima_agreement_no']);
        });
    }
}
