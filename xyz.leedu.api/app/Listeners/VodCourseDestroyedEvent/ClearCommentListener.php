<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Listeners\VodCourseDestroyedEvent;

use App\Constant\BusConstant;
use App\Events\VodCourseDestroyedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Leedu\ServiceV2\Services\CourseServiceInterface;
use App\Leedu\ServiceV2\Services\CommentServiceInterface;

class ClearCommentListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $commentService;

    protected $courseService;

    public function __construct(CommentServiceInterface $commentService, CourseServiceInterface $courseService)
    {
        $this->commentService = $commentService;
        $this->courseService = $courseService;
    }

    public function handle(VodCourseDestroyedEvent $event)
    {
        $this->commentService->deleteResourceComment(BusConstant::COMMENT_RT_VOD_COURSE, $event->id);

        $videos = $this->courseService->getCourseVideos($event->id, ['id']);
        if ($videos) {
            foreach ($videos as $tmpItem) {
                $this->commentService->deleteResourceComment(BusConstant::COMMENT_RT_VOD_COURSE_VIDEO, $tmpItem['id']);
            }
        }
    }
}
