<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Leedu\ServiceV2\Models\AppConfig;

class UpgradeV498
{
    public static function handle()
    {
        self::removeAppConfig();
    }

    private static function removeAppConfig()
    {
        AppConfig::query()
            ->whereIn('key', [
                'leedu.member.protocol',
                'leedu.member.private_protocol',
            ])
            ->update(['field_type' => 'textarea']);
    }
}
