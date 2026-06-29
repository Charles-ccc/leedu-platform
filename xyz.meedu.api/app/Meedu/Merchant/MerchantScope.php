<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：机构数据隔离全局作用域。
 * 仅当处于机构上下文时按 merchant_id 过滤；平台/全局视角不过滤（保持现有行为）。
 */

namespace App\Meedu\Merchant;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MerchantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        /** @var MerchantContext $context */
        $context = app(MerchantContext::class);
        if ($context->hasMerchant()) {
            $builder->where($model->getTable() . '.merchant_id', $context->getMerchantId());
        }
    }
}
