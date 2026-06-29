<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Listeners\VodVideoDestroyedEvent;

use App\Constant\BusConstant;
use App\Events\VodVideoDestroyedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Leedu\ServiceV2\Services\CommentServiceInterface;

class ClearCommentListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $commentService;

    public function __construct(CommentServiceInterface $commentService)
    {
        $this->commentService = $commentService;
    }

    public function handle(VodVideoDestroyedEvent $event)
    {
        $this->commentService->deleteResourceComment(BusConstant::COMMENT_RT_VOD_COURSE_VIDEO, $event->id);
    }
}
