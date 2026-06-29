<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：机构归属 trait。
 * 给模型挂上后：
 *  - 自动应用 MerchantScope（机构上下文下按 merchant_id 过滤）。
 *  - 创建记录时，若未显式指定 merchant_id 且处于机构上下文，自动写入当前机构ID。
 */

namespace App\Meedu\Merchant;

use App\Meedu\ServiceV2\Models\Merchant;

trait BelongsToMerchant
{
    public static function bootBelongsToMerchant()
    {
        static::addGlobalScope(new MerchantScope());

        static::creating(function ($model) {
            if (empty($model->merchant_id)) {
                /** @var MerchantContext $context */
                $context = app(MerchantContext::class);
                if ($context->hasMerchant()) {
                    $model->merchant_id = $context->getMerchantId();
                }
            }
        });
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}
