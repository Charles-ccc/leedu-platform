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
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VodCourseCommentHook implements HookRuntimeInterface
{
    public function handle(HookParams $params, \Closure $next)
    {
        $rt = $params->getValue('rt');
        $videoId = $params->getValue('rid');

        if (BusConstant::COMMENT_RT_VOD_COURSE_VIDEO === $rt) {
            /**
             * @var CourseServiceInterface $courseService
             */
            $courseService = app()->make(CourseServiceInterface::class);

            $video = $courseService->findVideo($videoId);
            if (!$video) {
                throw new ModelNotFoundException();
            }

            if (1 !== $video['is_allow_comment']) {
                throw new ServiceException(__('禁止提交评论'));
            }
        }
        $next($params);
    }
}
