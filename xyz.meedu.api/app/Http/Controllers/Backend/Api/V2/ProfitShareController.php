<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M4：平台分成分账记录(平台端查看/对账)。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Illuminate\Http\Request;
use App\Meedu\ServiceV2\Models\Merchant;
use App\Meedu\ServiceV2\Models\PlatformShareRecord;

class ProfitShareController extends BaseController
{
    private function ensurePlatform(): void
    {
        $admin = auth('administrator')->user();
        if (!$admin || (int)($admin->merchant_id ?? 0) !== 0) {
            abort(403, '只有平台管理员可以查看分账记录');
        }
    }

    public function index(Request $request)
    {
        $this->ensurePlatform();

        $status = $request->input('status');
        $merchantId = $request->input('merchant_id');

        $p = PlatformShareRecord::query()
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('status', (int)$status);
            })
            ->when($merchantId, function ($q) use ($merchantId) {
                $q->where('merchant_id', (int)$merchantId);
            })
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        $items = $p->items();
        $names = Merchant::query()
            ->whereIn('id', collect($items)->pluck('merchant_id')->unique()->all())
            ->pluck('name', 'id');
        $data = collect($items)->map(function ($r) use ($names) {
            $arr = $r->toArray();
            $arr['merchant_name'] = $names[$r->merchant_id] ?? '';
            return $arr;
        });

        // 汇总:平台分成总额(成功)
        $totalAmount = PlatformShareRecord::query()
            ->where('status', PlatformShareRecord::STATUS_SUCCESS)
            ->sum('amount');

        return $this->successData([
            'total' => $p->total(),
            'data' => $data,
            'settled_amount' => (int)$totalAmount,
        ]);
    }
}
