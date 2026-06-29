<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：协议模板管理(平台通用:网课买卖合同/芝麻代扣合同)。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Leedu\ServiceV2\Models\AgreementTemplate;

class AgreementTemplateController extends BaseController
{
    private function ensurePlatform(): void
    {
        $admin = auth('administrator')->user();
        if (!$admin || (int)($admin->merchant_id ?? 0) !== 0) {
            abort(403, '只有平台管理员可以管理协议模板');
        }
    }

    public function index(Request $request)
    {
        $this->ensurePlatform();
        $type = $request->input('type');

        $p = AgreementTemplate::query()
            ->where('merchant_id', 0)
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        return $this->successData(['total' => $p->total(), 'data' => $p->items()]);
    }

    public function store(Request $request)
    {
        $this->ensurePlatform();
        $data = $request->validate([
            'type' => ['required', 'in:course_sale,zhima_withhold'],
            'version' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:191'],
            'content' => ['required', 'string'],
        ]);

        // 同类型旧的生效版本下线
        AgreementTemplate::query()
            ->where('merchant_id', 0)
            ->where('type', $data['type'])
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        AgreementTemplate::create([
            'type' => $data['type'],
            'merchant_id' => 0,
            'version' => $data['version'],
            'title' => $data['title'] ?? '',
            'content' => $data['content'],
            'content_hash' => md5($data['content']),
            'is_active' => 1,
            'effective_at' => Carbon::now(),
        ]);

        return $this->success();
    }
}
