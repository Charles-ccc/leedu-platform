<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Models;

use App\Leedu\Merchant\BelongsToMerchant;

use Illuminate\Database\Eloquent\Model;

class MediaVideoCategory extends Model
{
    use BelongsToMerchant;
    protected $table = 'media_video_categories';

    protected $fillable = [
        'name', 'sort', 'admin_id', 'parent_id', 'parent_chain',
    ];
}
