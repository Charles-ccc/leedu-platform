<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M2：机构(商户)管理。仅平台管理员(merchant_id=0)可访问。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Illuminate\Http\Request;
use App\Models\Administrator;
use Illuminate\Support\Facades\Hash;
use App\Leedu\ServiceV2\Models\Merchant;
use App\Leedu\Merchant\MerchantAdminRole;

class MerchantController extends BaseController
{
    /**
     * 仅平台管理员可管理机构。
     */
    private function ensurePlatform(): void
    {
        $admin = auth('administrator')->user();
        if (!$admin || (int)($admin->merchant_id ?? 0) !== 0) {
            abort(403, '只有平台管理员可以管理机构');
        }
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'string', 'max:191'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'contact_name' => ['nullable', 'string', 'max:50'],
            'contact_mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'integer', 'in:0,1,2,3'],
            'platform_share_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'salesperson_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    private function filldata(Request $request): array
    {
        $data = $request->validate($this->rules());
        // 缺省值
        $data['slug'] = $data['slug'] ?? '';
        $data['logo'] = $data['logo'] ?? '';
        $data['intro'] = $data['intro'] ?? '';
        $data['contact_name'] = $data['contact_name'] ?? '';
        $data['contact_mobile'] = $data['contact_mobile'] ?? '';
        $data['status'] = $data['status'] ?? Merchant::STATUS_PENDING;
        $data['platform_share_rate'] = $data['platform_share_rate'] ?? 0;
        $data['salesperson_commission_rate'] = $data['salesperson_commission_rate'] ?? 0;
        return $data;
    }

    public function index(Request $request)
    {
        $this->ensurePlatform();

        $keyword = $request->input('keyword');
        $status = $request->input('status');

        $p = Merchant::query()
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%');
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('status', (int)$status);
            })
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        return $this->successData([
            'total' => $p->total(),
            'data' => $p->items(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensurePlatform();
        $data = $this->filldata($request);

        if (!empty($data['slug']) && Merchant::query()->where('slug', $data['slug'])->exists()) {
            return $this->error('机构标识(slug)已存在');
        }

        Merchant::create($data);
        return $this->success();
    }

    public function detail($id)
    {
        $this->ensurePlatform();
        return $this->successData(Merchant::query()->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $this->ensurePlatform();
        $merchant = Merchant::query()->findOrFail($id);
        $data = $this->filldata($request);

        if (!empty($data['slug']) && Merchant::query()->where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
            return $this->error('机构标识(slug)已存在');
        }

        $merchant->fill($data)->save();
        return $this->success();
    }

    /**
     * 公开:机构入驻申请。创建待审核机构 + 锁定的主账号(审核通过后解锁)。
     */
    public function apply(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'contact_name' => ['required', 'string', 'max:50'],
            'contact_mobile' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
            'intro' => ['nullable', 'string', 'max:1000'],
        ]);

        if (Administrator::query()->where('email', $data['email'])->exists()) {
            return $this->error('该邮箱已被占用');
        }

        $merchant = Merchant::create([
            'name' => $data['name'],
            'contact_name' => $data['contact_name'],
            'contact_mobile' => $data['contact_mobile'],
            'intro' => $data['intro'] ?? '',
            'status' => Merchant::STATUS_PENDING,
        ]);

        $role = MerchantAdminRole::ensure();
        $admin = new Administrator([
            'name' => $data['contact_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $admin->merchant_id = $merchant->id;
        $admin->is_ban_login = 1; // 审核通过前禁止登录
        $admin->save();
        $admin->roles()->sync([$role->id]);

        $merchant->owner_admin_id = $admin->id;
        $merchant->save();

        return $this->success();
    }

    /**
     * 平台:机构入驻审核(通过/驳回)。
     * body: action=pass|reject, remark
     */
    public function audit(Request $request, $id)
    {
        $this->ensurePlatform();
        $data = $request->validate([
            'action' => ['required', 'in:pass,reject'],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $merchant = Merchant::query()->findOrFail($id);

        if ($data['action'] === 'pass') {
            $merchant->status = Merchant::STATUS_NORMAL;
            $merchant->audit_remark = '';
            if ($merchant->owner_admin_id) {
                Administrator::query()->where('id', $merchant->owner_admin_id)->update(['is_ban_login' => 0]);
            }
        } else {
            $merchant->status = Merchant::STATUS_REJECTED;
            $merchant->audit_remark = $data['remark'] ?? '';
            if ($merchant->owner_admin_id) {
                Administrator::query()->where('id', $merchant->owner_admin_id)->update(['is_ban_login' => 1]);
            }
        }
        $merchant->save();

        return $this->success();
    }

    /**
     * 机构下的管理员账号列表。
     */
    public function admins($id)
    {
        $this->ensurePlatform();
        Merchant::query()->findOrFail($id);

        $admins = Administrator::query()
            ->where('merchant_id', (int)$id)
            ->select(['id', 'name', 'email', 'is_ban_login', 'last_login_date', 'created_at'])
            ->orderByDesc('id')
            ->get();

        return $this->successData($admins);
    }

    /**
     * 为机构创建管理员账号(机构主账号/子账号)。
     */
    public function storeAdmin(Request $request, $id)
    {
        $this->ensurePlatform();
        $merchant = Merchant::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        if (Administrator::query()->where('email', $data['email'])->exists()) {
            return $this->error('该邮箱已被占用');
        }

        // 机构管理员角色(自动创建并挂权限)
        $role = MerchantAdminRole::ensure();

        $admin = new Administrator([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $admin->merchant_id = (int)$id; // 非 fillable，显式赋值
        $admin->save();
        $admin->roles()->sync([$role->id]);

        // 若机构尚无主账号，记录为主账号
        if (empty($merchant->owner_admin_id)) {
            $merchant->owner_admin_id = $admin->id;
            $merchant->save();
        }

        return $this->success();
    }
}
