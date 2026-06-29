<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Services;

interface OtherServiceInterface
{
    public function storeUserUploadImage(
        int    $userId,
        string $group,
        string $disk,
        string $path,
        string $name,
        string $visitUrl,
        string $logApi,
        string $logIp,
        string $logUA
    ): void;

    public function storeOrUpdateMediaVideo(string $service, string $videoId, array $data): void;

    public function deleteMediaVideo(string $service, string $videoId): void;

    public function mediaVideoVisibilityToggle(string $service, string $videoId): void;

    public function deleteMediaVideos(string $service, array $videoIds):void;
}
