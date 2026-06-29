<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Listeners\LinkUpdateEvent;

use App\Events\LinkUpdateEvent;
use App\Leedu\Cache\Impl\LinkCache;

class CacheClearListener
{
    private $linkCache;

    public function __construct(LinkCache $linkCache)
    {
        $this->linkCache = $linkCache;
    }

    public function handle(LinkUpdateEvent $event)
    {
        $this->linkCache->destroy();
    }
}
