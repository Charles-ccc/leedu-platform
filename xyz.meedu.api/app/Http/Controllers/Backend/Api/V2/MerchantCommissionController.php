<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：机构端 - 本机构承担的业务员提成(待付/标记已付)。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Meedu\Merchant\MerchantContext;
use App\Meedu\ServiceV2\Models\Salesperson;
use App\Meedu\ServiceV2\Models\CommissionRecord;

class MerchantCommissionController extends BaseController
{
    private function ensureMerchant(): int
    {
        /** @var MerchantContext $ctx */
        $ctx = app(MerchantContext::class);
        if (!$ctx->hasMerchant()) {
            abort(403, '只有机构管理员可以查看本机构提成');
        }
        return (int)$ctx->getMerchantId();
    }

    public function index(Request $request)
    {
        $this->ensureMerchant(); // 机构上下文已自动按 merchant_id 过滤

        $payStatus = $request->input('pay_status');

        $p = CommissionRecord::query()
            ->when($payStatus !== null && $payStatus !== '', function ($q) use ($payStatus) {
                $q->where('pay_status', (int)$payStatus);
            })
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        $items = $p->items();
        $spNames = Salesperson::query()
            ->whereIn('id', collect($items)->pluck('salesperson_id')->unique()->all())
            ->pluck('name', 'id');
        $data = collect($items)->map(function ($r) use ($spNames) {
            $arr = $r->toArray();
            $arr['salesperson_name'] = $spNames[$r->salesperson_id] ?? '';
            return $arr;
        });

        // 待付合计
        $pendingSum = CommissionRecord::query()
            ->where('pay_status', CommissionRecord::PAY_PENDING)
            ->where('record_type', CommissionRecord::TYPE_ACCRUE)
            ->sum('amount');

        return $this->successData([
            'total' => $p->total(),
            'data' => $data,
            'pending_sum' => (int)$pendingSum,
        ]);
    }

    /**
     * 标记某笔提成已支付 → 计入业务员可提现余额。
     */
    public function pay($id)
    {
        $this->ensureMerchant();

        // 作用域确保只能操作本机构的记录
        $record = CommissionRecord::query()->findOrFail($id);
        if ($record->record_type !== CommissionRecord::TYPE_ACCRUE) {
            return $this->error('该记录无需支付');
        }
        if ($record->pay_status === CommissionRecord::PAY_PAID) {
            return $this->error('该提成已支付');
        }

        $record->pay_status = CommissionRecord::PAY_PAID;
        $record->paid_at = Carbon::now();
        $record->save();

        // 计入业务员可提现余额
        $sp = Salesperson::withoutGlobalScopes()->find($record->salesperson_id);
        if ($sp) {
            $sp->balance = (int)$sp->balance + (int)$record->amount;
            $sp->save();
        }

        return $this->success();
    }
}
