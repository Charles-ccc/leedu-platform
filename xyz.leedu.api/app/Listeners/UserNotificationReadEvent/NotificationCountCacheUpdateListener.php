<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Listeners\UserNotificationReadEvent;

use App\Events\UserNotificationReadEvent;
use App\Leedu\Cache\Impl\UserNotificationCountCache;

class NotificationCountCacheUpdateListener
{
    public function handle(UserNotificationReadEvent $event)
    {
        $cache = new UserNotificationCountCache();
        $cache->destroy($event->userId);
    }
}
