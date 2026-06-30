<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员提成计提与绑定。
 * - 客户绑定业务员(永久,可改派)。
 * - 下单即计提：提成=订单成交额 × 机构 salesperson_commission_rate，由订单所属机构承担。
 */

namespace App\Meedu\Merchant;

use Carbon\Carbon;
use App\Constant\BusConstant;
use App\Services\Course\Models\Course;
use App\Meedu\ServiceV2\Models\Merchant;
use App\Meedu\ServiceV2\Models\Salesperson;
use App\Meedu\ServiceV2\Models\CommissionRecord;
use App\Meedu\ServiceV2\Models\SalespersonUserRelation;

class CommissionService
{
    /**
     * 取客户当前绑定的业务员ID(无则0)。
     */
    public function resolveActiveSalesperson(int $userId): int
    {
        $rel = SalespersonUserRelation::query()
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();
        return $rel ? (int)$rel->salesperson_id : 0;
    }

    /**
     * 按推广码查在职业务员(空码/不存在/已离职返回 null)。
     */
    public function findActiveByInviteCode(string $inviteCode): ?Salesperson
    {
        $inviteCode = trim($inviteCode);
        if ($inviteCode === '') {
            return null;
        }
        return Salesperson::query()
            ->where('invite_code', $inviteCode)
            ->where('status', Salesperson::STATUS_ACTIVE)
            ->first();
    }

    /**
     * 绑定客户到业务员(永久)。已绑其他业务员则视为改派:旧关系失效、记来源。
     */
    public function bindUser(int $salespersonId, int $userId, int $reassignedFrom = 0): SalespersonUserRelation
    {
        SalespersonUserRelation::query()
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        return SalespersonUserRelation::create([
            'salesperson_id' => $salespersonId,
            'user_id' => $userId,
            'bound_at' => Carbon::now(),
            'is_active' => 1,
            'reassigned_from' => $reassignedFrom,
        ]);
    }

    /**
     * 由订单商品解析所属机构ID(课程→课程机构;VIP会员等→平台0)。
     */
    public function resolveMerchantIdFromGoods($goodsType, int $goodsId): int
    {
        if ($goodsType === BusConstant::ORDER_GOODS_TYPE_COURSE) {
            $course = Course::withoutGlobalScopes()->find($goodsId);
            return $course ? (int)$course->merchant_id : 0;
        }
        return 0;
    }

    /**
     * 下单即计提提成。返回计提记录(不满足条件返回 null)。
     * 幂等:同一订单已有正向计提则跳过。
     */
    public function accrue(int $merchantId, int $salespersonId, int $orderId, int $baseAmount): ?CommissionRecord
    {
        if ($merchantId <= 0 || $salespersonId <= 0 || $baseAmount <= 0) {
            return null;
        }

        $merchant = Merchant::withoutGlobalScopes()->find($merchantId);
        if (!$merchant) {
            return null;
        }
        $rate = (float)$merchant->salesperson_commission_rate;
        if ($rate <= 0) {
            return null;
        }

        $exists = CommissionRecord::withoutGlobalScopes()
            ->where('order_id', $orderId)
            ->where('record_type', CommissionRecord::TYPE_ACCRUE)
            ->first();
        if ($exists) {
            return $exists;
        }

        $amount = (int)round($baseAmount * $rate / 100);

        return CommissionRecord::create([
            'salesperson_id' => $salespersonId,
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'base_amount' => $baseAmount,
            'rate' => $rate,
            'amount' => $amount,
            'record_type' => CommissionRecord::TYPE_ACCRUE,
            'pay_status' => CommissionRecord::PAY_PENDING,
        ]);
    }

    /**
     * 退款冲正：订单退款后，按退款金额比例生成负向冲正记录。
     * 「追回 + 下期扣抵」同时：扣减业务员余额(已付则追回;未付则余额转负=下期扣抵)。
     * 幂等：同一订单已冲正则跳过。
     */
    public function clawbackForOrder(int $orderId, int $refundAmount): ?CommissionRecord
    {
        $accrue = CommissionRecord::withoutGlobalScopes()
            ->where('order_id', $orderId)
            ->where('record_type', CommissionRecord::TYPE_ACCRUE)
            ->first();
        if (!$accrue) {
            return null;
        }
        $already = CommissionRecord::withoutGlobalScopes()
            ->where('ref_record_id', $accrue->id)
            ->where('record_type', CommissionRecord::TYPE_CLAWBACK)
            ->exists();
        if ($already) {
            return null;
        }

        // 按退款比例冲正(退款金额/成交额)
        $base = max(1, (int)$accrue->base_amount);
        $ratio = min(1, max(0, $refundAmount / $base));
        $clawAmount = (int)round($accrue->amount * $ratio); // 正值

        $record = CommissionRecord::create([
            'salesperson_id' => $accrue->salesperson_id,
            'merchant_id' => $accrue->merchant_id,
            'order_id' => $orderId,
            'base_amount' => $refundAmount,
            'rate' => $accrue->rate,
            'amount' => -$clawAmount, // 负向
            'record_type' => CommissionRecord::TYPE_CLAWBACK,
            'ref_record_id' => $accrue->id,
            'pay_status' => CommissionRecord::PAY_PAID, // 冲正记录无需机构再支付
            'remark' => '订单退款冲正',
        ]);

        // 追回+下期扣抵:扣减业务员余额
        $sp = Salesperson::withoutGlobalScopes()->find($accrue->salesperson_id);
        if ($sp) {
            $sp->balance = (int)$sp->balance - $clawAmount;
            $sp->save();
        }

        return $record;
    }

    /**
     * 批量改派:把某业务员名下有效客户全部改派给新业务员(离职处理)。
     * 仅影响之后新订单的提成归属;历史提成不变。返回改派条数。
     */
    public function reassignAll(int $fromSalespersonId, int $toSalespersonId): int
    {
        $relations = SalespersonUserRelation::query()
            ->where('salesperson_id', $fromSalespersonId)
            ->where('is_active', 1)
            ->get();

        $count = 0;
        foreach ($relations as $rel) {
            $this->bindUser($toSalespersonId, (int)$rel->user_id, $fromSalespersonId);
            $count++;
        }
        return $count;
    }
}
