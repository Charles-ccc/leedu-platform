<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：订单退款成功 → 业务员提成冲正(追回+下期扣抵)。
 */

namespace App\Listeners\OrderRefundProcessed;

use App\Events\OrderRefundProcessed;
use App\Leedu\Merchant\CommissionService;
use App\Services\Order\Models\OrderRefund;

class CommissionClawbackListener
{
    public function handle(OrderRefundProcessed $event)
    {
        // 仅退款成功时冲正
        if ((int)$event->status !== OrderRefund::STATUS_SUCCESS) {
            return;
        }

        $refund = $event->orderRefund;
        $orderId = (int)($refund['order_id'] ?? 0);
        if (!$orderId) {
            return;
        }
        // 退款金额(优先实退 amount,无则 total_amount)
        $refundAmount = (int)($refund['amount'] ?? $refund['total_amount'] ?? 0);
        if ($refundAmount <= 0) {
            $refundAmount = (int)($refund['total_amount'] ?? 0);
        }

        app(CommissionService::class)->clawbackForOrder($orderId, $refundAmount);
    }
}
