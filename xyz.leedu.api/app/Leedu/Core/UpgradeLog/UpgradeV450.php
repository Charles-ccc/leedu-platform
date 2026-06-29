<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Core\UpgradeLog;

use App\Services\Base\Model\AppConfig;
use App\Models\AdministratorPermission;

class UpgradeV450
{
    public static function handle()
    {
        self::removeConfig();
        self::configRename();
        self::removePermission();
        self::updateImageDiskOptions();
        self::updateSmsOptions();
        self::ampaConfigFixed();
    }

    public static function removePermission()
    {
        AdministratorPermission::query()
            ->whereIn('slug', [
                'indexBanner',
                'indexBanner.create',
                'indexBanner.store',
                'indexBanner.edit',
                'indexBanner.update',
                'indexBanner.destroy',
            ])
            ->delete();
    }

    public static function removeConfig()
    {
        AppConfig::query()
            ->whereIn('key', [
                // 全局js
                'leedu.system.js',
                // PC全局css
                'leedu.system.css.pc',
                // H5全局css
                'leedu.system.css.h5',
                // 会员注册默认激活
                'leedu.member.is_active_default',

                // SEO
                'leedu.seo.index.title',
                'leedu.seo.index.keywords',
                'leedu.seo.index.description',
                'leedu.seo.course_list.title',
                'leedu.seo.course_list.keywords',
                'leedu.seo.course_list.description',
                'leedu.seo.role_list.title',
                'leedu.seo.role_list.keywords',
                'leedu.seo.role_list.description',

                // 其它配置
                'leedu.other.course_list_page_size',
                'leedu.other.video_list_page_size',

                'leedu.system.editor',

                // 腾讯云超级播放器配置
                'leedu.system.player.tencent_pcfg',

                // 语言配置
                'leedu.system.lang',
            ])
            ->delete();
    }

    public static function configRename()
    {
        AppConfig::query()->where('key', 'leedu.system.icp')->update(['name' => 'ICP备案号']);
    }

    public static function updateImageDiskOptions()
    {
        AppConfig::query()
            ->where('key', 'leedu.upload.image.disk')
            ->update([
                'option_value' => json_encode([
                    [
                        'title' => '本地',
                        'key' => 'public',
                    ],
                    [
                        'title' => '阿里云OSS',
                        'key' => 'oss',
                    ],
                    [
                        'title' => '腾讯云COS',
                        'key' => 'cos',
                    ],
                    [
                        'title' => '七牛云',
                        'key' => 'qiniu',
                    ],
                ]),
            ]);
    }

    public static function updateSmsOptions()
    {
        AppConfig::query()
            ->where('key', 'leedu.system.sms')
            ->update([
                'option_value' => json_encode([
                    [
                        'title' => '阿里云',
                        'key' => 'aliyun',
                    ],
                    [
                        'title' => '腾讯云',
                        'key' => 'tencent',
                    ],
                    [
                        'title' => '云片',
                        'key' => 'yunpian',
                    ],
                ]),
            ]);
    }

    public static function ampaConfigFixed()
    {
        AppConfig::query()->where('key', 'leedu.services.imap.key')->delete();

        $data = [
            'group' => '高德地图',
            'name' => '应用Key',
            'field_type' => 'text',
            'sort' => 1,
            'key' => 'leedu.services.amap.key',
            'value' => '',
            'is_private' => 1,
        ];

        if (!AppConfig::query()->where('key', $data['key'])->exists()) {
            AppConfig::create($data);
        }
    }
}
