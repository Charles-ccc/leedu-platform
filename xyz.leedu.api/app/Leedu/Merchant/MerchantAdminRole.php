<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M2：机构管理员角色。
 * 自动创建/维护「机构管理员」角色，并挂上机构可用的权限子集
 * (课程/视频/分类/章节/附件/媒资/订单/学员/评论/优惠码/主页)，
 * 排除平台级权限(管理员/角色/设置/装修/导航/公告/审计/敏感数据)。
 */

namespace App\Leedu\Merchant;

use App\Models\AdministratorRole;
use App\Models\AdministratorPermission;

class MerchantAdminRole
{
    const SLUG = 'merchant-admin';

    /**
     * 机构管理员可用的权限 slug 前缀。
     */
    const PERMISSION_PREFIXES = [
        'dashboard',
        'course',      // course / course.* / courseCategory / course_chapter / course_attach
        'video',       // video / video.*
        'media.',      // 媒资库
        'order',       // order / order.*
        'member',      // 学员
        'comment.',    // 评论
        'promoCode',   // 优惠码
    ];

    /**
     * 确保角色存在并已挂权限，返回角色。
     */
    public static function ensure(): AdministratorRole
    {
        $role = AdministratorRole::query()->where('slug', self::SLUG)->first();
        if (!$role) {
            $role = AdministratorRole::create([
                'display_name' => '机构管理员',
                'slug' => self::SLUG,
                'description' => '机构(商户)管理员，仅能管理本机构数据',
            ]);
        }

        $permissionIds = AdministratorPermission::query()
            ->where(function ($q) {
                foreach (self::PERMISSION_PREFIXES as $prefix) {
                    $q->orWhere('slug', 'like', $prefix . '%');
                }
            })
            ->pluck('id')
            ->toArray();

        $role->permissions()->sync($permissionIds);

        return $role;
    }
}
