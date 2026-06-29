<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：管理员加 merchant_id。0=平台管理员，>0=机构管理员。
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMerchantIdToAdministratorsTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('administrators', 'merchant_id')) {
            return;
        }
        Schema::table('administrators', function (Blueprint $table) {
            $table->unsignedBigInteger('merchant_id')->default(0)->after('id')
                ->comment('所属机构ID，0=平台管理员');
            $table->index(['merchant_id'], 'idx_merchant_id');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('administrators', 'merchant_id')) {
            return;
        }
        Schema::table('administrators', function (Blueprint $table) {
            $table->dropIndex('idx_merchant_id');
            $table->dropColumn('merchant_id');
        });
    }
}
