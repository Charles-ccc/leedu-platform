<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderPaidRecord extends Model
{
    use BelongsToMerchant;
    use SoftDeletes, HasFactory;

    protected $table = 'order_paid_records';

    protected $fillable = [
        'user_id', 'order_id', 'paid_total', 'paid_type', 'paid_type_id',
    ];
}
