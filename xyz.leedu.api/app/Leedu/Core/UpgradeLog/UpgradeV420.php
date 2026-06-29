<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Services\Base\Model\AppConfig;

class UpgradeV420
{
    public static function handle()
    {
        self::removeConfig();
    }

    public static function removeConfig()
    {
        AppConfig::query()
            ->whereIn('key', [
                // 白色logo配置
                'leedu.system.white_logo',
                // 阿里云私密播放配置
                'leedu.system.player.enabled_aliyun_private',
            ])
            ->delete();
    }
}
