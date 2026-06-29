<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Models\AdministratorPermission;

class UpgradeV4915
{
    public static function handle()
    {
        self::deletePermissions();
    }

    private static function deletePermissions()
    {
        AdministratorPermission::query()
            ->whereIn('slug', [
                'video.upload.tencent.token',
                'video.upload.aliyun.token',
            ])
            ->delete();
    }
}
