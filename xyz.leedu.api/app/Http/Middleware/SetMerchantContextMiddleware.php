<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M2：把当前登录管理员的机构注入 MerchantContext。
 * - 机构管理员(merchant_id>0)：注入机构上下文 → 之后所有 Eloquent 查询按机构隔离。
 * - 平台管理员(merchant_id=0)/未登录：不注入 → 平台/全局视角，可跨机构。
 */

namespace App\Http\Middleware;

use Closure;
use App\Leedu\Merchant\MerchantContext;

class SetMerchantContextMiddleware
{
    public function handle($request, Closure $next)
    {
        /** @var MerchantContext $context */
        $context = app(MerchantContext::class);

        try {
            // administrator 守卫为 JWT，可直接从请求 token 解析当前管理员
            $admin = auth('administrator')->user();
        } catch (\Throwable $e) {
            $admin = null;
        }

        if ($admin && (int)($admin->merchant_id ?? 0) > 0) {
            $context->set((int)$admin->merchant_id);
        }

        return $next($request);
    }
}
