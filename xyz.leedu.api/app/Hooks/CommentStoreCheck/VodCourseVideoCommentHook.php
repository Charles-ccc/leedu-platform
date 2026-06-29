<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Hooks\CommentStoreCheck;

use App\Constant\BusConstant;
use App\Leedu\Hooks\HookParams;
use App\Exceptions\ServiceException;
use App\Leedu\Hooks\HookRuntimeInterface;
use App\Leedu\ServiceV2\Services\CourseServiceInterface;

class VodCourseVideoCommentHook implements HookRuntimeInterface
{
    public function handle(HookParams $params, \Closure $next)
    {
        $rt = $params->getValue('rt');
        $courseId = $params->getValue('rid');

        if (BusConstant::COMMENT_RT_VOD_COURSE === $rt) {
            /**
             * @var CourseServiceInterface $courseService
             */
            $courseService = app()->make(CourseServiceInterface::class);

            $course = $courseService->findOrFail($courseId);

            if (1 !== $course['is_allow_comment']) {
                throw new ServiceException(__('禁止提交评论'));
            }
        }
        $next($params);
    }
}
