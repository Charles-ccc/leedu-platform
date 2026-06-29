<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Services\Course\Proxies;

use App\Constant\CacheConstant;
use App\Leedu\ServiceProxy\ServiceProxy;
use App\Leedu\ServiceProxy\Cache\CacheInfo;
use App\Services\Course\Services\CourseService;
use App\Services\Course\Interfaces\CourseServiceInterface;

class CourseServiceProxy extends ServiceProxy implements CourseServiceInterface
{
    public function __construct(CourseService $service)
    {
        parent::__construct($service);
        $this->cache['chapters'] = function ($courseId) {
            return new CacheInfo(
                get_cache_key(CacheConstant::COURSE_SERVICE_CHAPTERS['name'], $courseId),
                $this->configService->getCacheExpire()
            );
        };
        $this->cache['simplePage'] = function (int $page, int $pageSize, int $categoryId = 0) {
            return new CacheInfo(
                get_cache_key(CacheConstant::COURSE_SERVICE_PAGINATOR['name'], $page, $pageSize, $categoryId),
                $this->configService->getCacheExpire()
            );
        };
    }
}
