<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M1：当前请求的机构上下文。
 * - 未设置 / merchant_id<=0：平台或全局视角，不做机构过滤。
 * - merchant_id>0：机构视角，全局作用域按该机构过滤。
 * 以单例注册到容器（见 AppServiceProvider::register）。
 */

namespace App\Leedu\Merchant;

class MerchantContext
{
    /**
     * @var int|null 当前机构ID；null 或 <=0 表示平台/全局
     */
    protected $merchantId = null;

    public function set(?int $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function clear(): void
    {
        $this->merchantId = null;
    }

    public function getMerchantId(): ?int
    {
        return $this->merchantId;
    }

    /**
     * 是否处于"机构"上下文（需要按 merchant_id 过滤）。
     */
    public function hasMerchant(): bool
    {
        return $this->merchantId !== null && $this->merchantId > 0;
    }

    /**
     * 是否平台/全局视角（不过滤）。
     */
    public function isPlatform(): bool
    {
        return !$this->hasMerchant();
    }
}
