<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Leedu\ServiceV2\Models\AppConfig;

class UpgradeV4927
{
    public static function handle()
    {
        self::hideSomeConfigItems();
    }

    private static function hideSomeConfigItems()
    {
        AppConfig::query()
            ->whereIn('key', [
                'leedu.member.protocol',
                'leedu.member.private_protocol',
                'leedu.member.vip_protocol',
            ])
            ->update(['is_show' => 0]);
    }
}
