<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Services;

interface CommentServiceInterface
{
    public function comments(int $rt, int $rid): array;

    public function replies(int $rt, int $rid, int $parentId): array;

    public function create(array $data): array;

    public function deleteUserDATA(int $userId): void;

    public function deleteResourceComment(string $rt, int $rid): void;
}
