<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Leedu\ServiceV2\Models\AppConfig;

class UpgradeV4914
{
    public static function handle()
    {
        self::deleteAppConfigs();
        self::configRename();
    }

    private static function configRename()
    {
        AppConfig::query()
            ->where('key', 'leedu.payment.wechat.enabled')
            ->update([
                'name' => '微信支付',
            ]);
    }

    private static function deleteAppConfigs()
    {
        AppConfig::query()
            ->whereIn('key', [
                'leedu.payment.wechat-jsapi.enabled',
            ])
            ->delete();
    }
}
