<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M3：支付成功后生成订单分期记录(一次性付款=1期,标记已扣)。
 * 为后续平台分成(M4)、芝麻先享分期(M5)提供数据基础。
 */

namespace App\Listeners\PaymentSuccessEvent;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Meedu\Merchant\ProfitShareService;
use App\Meedu\ServiceV2\Models\OrderInstallment;

class OrderInstallmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event)
    {
        $order = $event->order;
        $orderId = (int)($order['id'] ?? 0);
        if (!$orderId) {
            return;
        }

        // 幂等:已有分期记录则跳过
        if (OrderInstallment::withoutGlobalScopes()->where('order_id', $orderId)->exists()) {
            return;
        }

        $installment = OrderInstallment::create([
            'order_id' => $orderId,
            'merchant_id' => (int)($order['merchant_id'] ?? 0),
            'period_no' => 1,
            'amount' => (int)($order['charge'] ?? 0),
            'status' => OrderInstallment::STATUS_CHARGED,
            'charged_at' => now(),
        ]);

        // M4: 该期扣款成功 → 生成平台分成分账记录
        app(ProfitShareService::class)->settleInstallment($installment);
    }
}
