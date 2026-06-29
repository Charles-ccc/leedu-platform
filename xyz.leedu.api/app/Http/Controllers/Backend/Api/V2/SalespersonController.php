<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M6：业务员管理 + 提成记录(平台端)。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Leedu\Merchant\CommissionService;
use App\Leedu\ServiceV2\Models\Merchant;
use App\Leedu\ServiceV2\Models\Salesperson;
use App\Leedu\ServiceV2\Models\CommissionRecord;
use App\Leedu\ServiceV2\Models\SalespersonWithdrawal;

class SalespersonController extends BaseController
{
    private function ensurePlatform(): void
    {
        $admin = auth('administrator')->user();
        if (!$admin || (int)($admin->merchant_id ?? 0) !== 0) {
            abort(403, '只有平台管理员可以管理业务员');
        }
    }

    public function index(Request $request)
    {
        $this->ensurePlatform();
        $keyword = $request->input('keyword');

        $p = Salesperson::query()
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('mobile', 'like', '%' . $keyword . '%');
            })
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        return $this->successData(['total' => $p->total(), 'data' => $p->items()]);
    }

    public function store(Request $request)
    {
        $this->ensurePlatform();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'alipay_account' => ['nullable', 'string', 'max:191'],
            'invite_code' => ['nullable', 'string', 'max:32'],
        ]);

        $inviteCode = $data['invite_code'] ?? '';
        if (!$inviteCode) {
            $inviteCode = strtoupper(Str::random(8));
        }
        if (Salesperson::query()->where('invite_code', $inviteCode)->exists()) {
            return $this->error('推广码已存在');
        }

        Salesperson::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'] ?? '',
            'alipay_account' => $data['alipay_account'] ?? '',
            'invite_code' => $inviteCode,
            'status' => Salesperson::STATUS_ACTIVE,
        ]);

        return $this->success();
    }

    public function update(Request $request, $id)
    {
        $this->ensurePlatform();
        $sp = Salesperson::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'alipay_account' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'integer', 'in:1,2'],
        ]);

        $sp->name = $data['name'];
        $sp->mobile = $data['mobile'] ?? '';
        $sp->alipay_account = $data['alipay_account'] ?? '';
        if (isset($data['status'])) {
            $sp->status = $data['status'];
        }
        $sp->save();

        return $this->success();
    }

    /**
     * 手动绑定客户到业务员(后台运营用)。
     */
    public function bind(Request $request, $id)
    {
        $this->ensurePlatform();
        $sp = Salesperson::query()->findOrFail($id);
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        app(CommissionService::class)->bindUser((int)$sp->id, (int)$data['user_id']);
        return $this->success();
    }

    /**
     * 提成记录列表(平台)。
     */
    public function commissions(Request $request)
    {
        $this->ensurePlatform();

        $salespersonId = $request->input('salesperson_id');
        $payStatus = $request->input('pay_status');

        $p = CommissionRecord::query()
            ->when($salespersonId, function ($q) use ($salespersonId) {
                $q->where('salesperson_id', (int)$salespersonId);
            })
            ->when($payStatus !== null && $payStatus !== '', function ($q) use ($payStatus) {
                $q->where('pay_status', (int)$payStatus);
            })
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        $items = $p->items();
        $spNames = Salesperson::query()
            ->whereIn('id', collect($items)->pluck('salesperson_id')->unique()->all())
            ->pluck('name', 'id');
        $mNames = Merchant::query()
            ->whereIn('id', collect($items)->pluck('merchant_id')->unique()->all())
            ->pluck('name', 'id');

        $data = collect($items)->map(function ($r) use ($spNames, $mNames) {
            $arr = $r->toArray();
            $arr['salesperson_name'] = $spNames[$r->salesperson_id] ?? '';
            $arr['merchant_name'] = $mNames[$r->merchant_id] ?? '';
            return $arr;
        });

        return $this->successData(['total' => $p->total(), 'data' => $data]);
    }

    /**
     * 客户改派:把某业务员名下有效客户全部改派给另一业务员(离职处理)。
     */
    public function reassign(Request $request)
    {
        $this->ensurePlatform();
        $data = $request->validate([
            'from_salesperson_id' => ['required', 'integer', 'min:1'],
            'to_salesperson_id' => ['required', 'integer', 'min:1', 'different:from_salesperson_id'],
        ]);

        $count = app(CommissionService::class)
            ->reassignAll((int)$data['from_salesperson_id'], (int)$data['to_salesperson_id']);

        return $this->successData(['reassigned' => $count]);
    }

    /**
     * 提现记录列表。
     */
    public function withdrawals(Request $request)
    {
        $this->ensurePlatform();

        $p = SalespersonWithdrawal::query()
            ->when($request->input('salesperson_id'), function ($q) use ($request) {
                $q->where('salesperson_id', (int)$request->input('salesperson_id'));
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

        return $this->successData(['total' => $p->total(), 'data' => $data]);
    }

    /**
     * 平台为业务员发起提现(从可提现余额扣除,生成待打款记录)。
     */
    public function withdrawalCreate(Request $request)
    {
        $this->ensurePlatform();
        $data = $request->validate([
            'salesperson_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $sp = Salesperson::query()->findOrFail($data['salesperson_id']);
        if ((int)$sp->balance < (int)$data['amount']) {
            return $this->error('可提现余额不足');
        }

        $sp->balance = (int)$sp->balance - (int)$data['amount'];
        $sp->save();

        SalespersonWithdrawal::create([
            'salesperson_id' => $sp->id,
            'amount' => (int)$data['amount'],
            'alipay_account' => $sp->alipay_account,
            'status' => SalespersonWithdrawal::STATUS_PENDING,
        ]);

        return $this->success();
    }

    /**
     * 处理提现:标记已打款 / 拒绝(退回余额)。
     */
    public function withdrawalProcess(Request $request, $id)
    {
        $this->ensurePlatform();
        $data = $request->validate([
            'action' => ['required', 'in:pay,reject'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $w = SalespersonWithdrawal::query()->findOrFail($id);
        if ($w->status !== SalespersonWithdrawal::STATUS_PENDING) {
            return $this->error('该提现已处理');
        }

        if ($data['action'] === 'pay') {
            $w->status = SalespersonWithdrawal::STATUS_PAID;
        } else {
            $w->status = SalespersonWithdrawal::STATUS_REJECTED;
            // 退回余额
            $sp = Salesperson::query()->find($w->salesperson_id);
            if ($sp) {
                $sp->balance = (int)$sp->balance + (int)$w->amount;
                $sp->save();
            }
        }
        $w->audit_remark = $data['remark'] ?? '';
        $w->save();

        return $this->success();
    }
}

