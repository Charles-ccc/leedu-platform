<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：芝麻先享(先学后付)服务。
 * - 合同模板解析(机构优先,回退平台通用)。
 * - 合同勾选同意存证、实名核身+代扣签约两层存证(当前态 + append-only事件)。
 * - 分期计划生成。
 * 注:真实核身/签约/扣款调用支付宝芝麻信用接口,随凭证联调;此处为流程框架+存证。
 */

namespace App\Leedu\Merchant;

use Carbon\Carbon;
use App\Leedu\ServiceV2\Models\ZhimaSignEvent;
use App\Leedu\ServiceV2\Models\UserZhimaSigning;
use App\Leedu\ServiceV2\Models\AgreementTemplate;
use App\Leedu\ServiceV2\Models\OrderInstallment;
use App\Leedu\ServiceV2\Models\UserContractConsent;

class ZhimaService
{
    /**
     * 取某类型当前生效合同(机构自定义优先,无则平台通用 merchant_id=0)。
     */
    public function activeTemplate(string $type, int $merchantId): ?AgreementTemplate
    {
        return AgreementTemplate::query()
            ->where('type', $type)
            ->where('is_active', 1)
            ->whereIn('merchant_id', array_unique([$merchantId, 0]))
            ->orderByDesc('merchant_id')
            ->first();
    }

    /**
     * 记录合同勾选同意(append-only存证)。
     */
    public function recordConsent(int $userId, int $orderId, int $merchantId, AgreementTemplate $tpl, string $ip, string $ua): UserContractConsent
    {
        return UserContractConsent::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'merchant_id' => $merchantId,
            'agreement_type' => $tpl->type,
            'agreement_version' => $tpl->version,
            'agreement_hash' => $tpl->content_hash,
            'consented_at' => Carbon::now(),
            'consent_ip' => $ip,
            'consent_ua' => $ua,
        ]);
    }

    /**
     * 是否已有有效签约(用户×机构)。
     */
    public function hasValidSigning(int $userId, int $merchantId): bool
    {
        $s = UserZhimaSigning::query()
            ->where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->first();
        if (!$s || $s->sign_status !== UserZhimaSigning::SIGN_SIGNED) {
            return false;
        }
        return !$s->expired_at || $s->expired_at->isFuture();
    }

    /**
     * 签约成功回调:两层存证。
     * - user_zhima_signings:当前有效态(updateOrCreate)。
     * - zhima_sign_events:append-only 历史留痕。
     */
    public function recordSigning(array $d): UserZhimaSigning
    {
        $signing = UserZhimaSigning::query()->updateOrCreate(
            ['user_id' => $d['user_id'], 'merchant_id' => $d['merchant_id']],
            [
                'alipay_open_id' => $d['alipay_open_id'] ?? '',
                'real_name' => $d['real_name'] ?? '',
                'cert_no_enc' => $d['cert_no'] ?? null,
                'verify_status' => UserZhimaSigning::VERIFY_PASSED,
                'verify_channel' => $d['verify_channel'] ?? 'alipay',
                'verified_at' => Carbon::now(),
                'agreement_no' => $d['agreement_no'] ?? '',
                'sign_status' => UserZhimaSigning::SIGN_SIGNED,
                'signed_at' => Carbon::now(),
                'expired_at' => $d['expired_at'] ?? null,
            ]
        );

        ZhimaSignEvent::create([
            'user_id' => $d['user_id'],
            'alipay_open_id' => $d['alipay_open_id'] ?? '',
            'merchant_id' => $d['merchant_id'],
            'agreement_no' => $d['agreement_no'] ?? '',
            'agreement_version' => $d['agreement_version'] ?? '',
            'agreement_hash' => $d['agreement_hash'] ?? '',
            'signed_at' => Carbon::now(),
            'sign_ip' => $d['ip'] ?? '',
            'sign_ua' => $d['ua'] ?? '',
            'raw_callback' => $d['raw'] ?? '',
        ]);

        return $signing;
    }

    /**
     * 生成分期计划(不落库)。总额平摊到 N 期,余数计入首期;首期now,其后每 cycleDays 一期。
     */
    public function buildInstallmentPlan(int $total, int $periods, int $cycleDays): array
    {
        $periods = max(1, $periods);
        $per = intdiv($total, $periods);
        $remainder = $total - $per * $periods;

        $plan = [];
        for ($i = 1; $i <= $periods; $i++) {
            $amount = $per + ($i === 1 ? $remainder : 0);
            $plan[] = [
                'period_no' => $i,
                'amount' => $amount,
                'plan_charge_at' => Carbon::now()->addDays($cycleDays * ($i - 1)),
            ];
        }
        return $plan;
    }

    /**
     * 为芝麻先享订单创建多期扣款计划(首期待扣,其余按周期排期)。
     */
    public function createInstallmentsForOrder(int $orderId, int $merchantId, int $total, int $periods, int $cycleDays): void
    {
        if (OrderInstallment::withoutGlobalScopes()->where('order_id', $orderId)->exists()) {
            return; // 幂等
        }
        foreach ($this->buildInstallmentPlan($total, $periods, $cycleDays) as $p) {
            OrderInstallment::create([
                'order_id' => $orderId,
                'merchant_id' => $merchantId,
                'period_no' => $p['period_no'],
                'amount' => $p['amount'],
                'plan_charge_at' => $p['plan_charge_at'],
                'status' => OrderInstallment::STATUS_PENDING,
            ]);
        }
    }
}
