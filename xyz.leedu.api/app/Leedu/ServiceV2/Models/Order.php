<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use App\Constant\TableConstant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use BelongsToMerchant;
    use HasFactory;

    protected $table = TableConstant::TABLE_ORDERS;

    protected $fillable = [
        'user_id', 'charge', 'status', 'order_id', 'payment',
        'payment_method', 'is_refund', 'last_refund_at',
        'agreement_id',
        // 平台化
        'merchant_id', 'bound_salesperson_id', 'pay_type', 'total_periods', 'zhima_agreement_no',
    ];
}
