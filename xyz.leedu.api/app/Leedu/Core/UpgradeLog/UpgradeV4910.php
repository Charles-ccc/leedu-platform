<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Leedu\ServiceV2\Models\AppConfig;

class UpgradeV4910
{
    public static function handle()
    {
        AppConfig::query()->where('key', 'leedu.member.protocol')->limit(1)->update(['name' => '注册协议']);
        AppConfig::query()->where('key', 'leedu.member.private_protocol')->limit(1)->update(['name' => '隐私政策']);
    }
}
