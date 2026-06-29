<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\ServiceV2\Services;

interface OrderServiceInterface
{
    public function findById(int $orderId): array;

    public function getOrderGoodsListById($id): array;

    public function getOrderPaidRecordsById($id): array;

    public function changePaymentAndMethod($id, string $payment, string $paymentMethod): int;

    public function createOrder(int $userId, $orderGoodsInfo, $promoCode): array;

    public function findByOrderNo(string $orderNo): array;
}
