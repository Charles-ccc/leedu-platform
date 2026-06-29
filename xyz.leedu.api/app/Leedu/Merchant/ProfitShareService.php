<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M4：平台分成分账。
 * 每期扣款成功后，按机构 platform_share_rate 计算平台分成并落记录。
 * 真实分账(调支付宝分账API)随支付凭证联调，落记录后置 SUCCESS+settle_no。
 */

namespace App\Leedu\Merchant;

use App\Leedu\ServiceV2\Models\Merchant;
use App\Leedu\ServiceV2\Models\OrderInstallment;
use App\Leedu\ServiceV2\Models\PlatformShareRecord;

class ProfitShareService
{
    /**
     * 为某一期扣款生成平台分成记录。
     * - 平台自营(merchant_id<=0)或机构未设分成比例：不分账，返回 null。
     * - 幂等：同一期已分账则返回已有记录。
     */
    public function settleInstallment(OrderInstallment $installment): ?PlatformShareRecord
    {
        $merchantId = (int)$installment->merchant_id;
        if ($merchantId <= 0) {
            return null; // 平台自营，无需分账
        }

        /** @var Merchant $merchant */
        $merchant = Merchant::withoutGlobalScopes()->find($merchantId);
        if (!$merchant) {
            return null;
        }

        $rate = (float)$merchant->platform_share_rate;
        if ($rate <= 0) {
            return null; // 未设平台分成
        }

        // 幂等
        $exists = PlatformShareRecord::withoutGlobalScopes()
            ->where('order_installment_id', $installment->id)
            ->first();
        if ($exists) {
            return $exists;
        }

        $amount = (int)round($installment->amount * $rate / 100);

        $record = PlatformShareRecord::create([
            'order_id' => $installment->order_id,
            'order_installment_id' => $installment->id,
            'merchant_id' => $merchantId,
            'rate' => $rate,
            'base_amount' => (int)$installment->amount,
            'amount' => $amount,
            'status' => PlatformShareRecord::STATUS_PENDING,
        ]);

        // TODO(联调): 调用支付宝分账API将 $amount 分到平台账户，
        // 成功后: $record->update(['status'=>STATUS_SUCCESS, 'alipay_settle_no'=>$no]);

        return $record;
    }
}
