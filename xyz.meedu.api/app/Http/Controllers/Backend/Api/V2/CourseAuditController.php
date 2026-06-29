<?php

/*
 * This file is part of the MeEdu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M2：课程审核。
 * - 机构端：提交课程审核(submit)。
 * - 平台端：待审列表(pending) + 审核通过/驳回(audit)。
 */

namespace App\Http\Controllers\Backend\Api\V2;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Meedu\ServiceV2\Models\Course;
use App\Meedu\ServiceV2\Models\Merchant;
use App\Meedu\Merchant\MerchantContext;

class CourseAuditController extends BaseController
{
    private function admin()
    {
        return auth('administrator')->user();
    }

    private function ensurePlatform(): void
    {
        $admin = $this->admin();
        if (!$admin || (int)($admin->merchant_id ?? 0) !== 0) {
            abort(403, '只有平台管理员可以审核课程');
        }
    }

    /**
     * 平台端：待审核课程列表。
     */
    public function pending(Request $request)
    {
        $this->ensurePlatform();

        $p = Course::query()
            ->where('merchant_id', '>', 0)
            ->where('audit_status', Course::AUDIT_PENDING)
            ->select(['id', 'merchant_id', 'title', 'thumb', 'charge', 'submitted_at', 'created_at'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate((int)$request->input('size', 10));

        // 附机构名称
        $items = $p->items();
        $merchantNames = Merchant::query()
            ->whereIn('id', collect($items)->pluck('merchant_id')->unique()->all())
            ->pluck('name', 'id');
        $data = collect($items)->map(function ($c) use ($merchantNames) {
            $arr = $c->toArray();
            $arr['merchant_name'] = $merchantNames[$c->merchant_id] ?? '';
            return $arr;
        });

        return $this->successData([
            'total' => $p->total(),
            'data' => $data,
        ]);
    }

    /**
     * 平台端：审核通过/驳回。
     * body: action=pass|reject, remark
     */
    public function audit(Request $request, $id)
    {
        $this->ensurePlatform();

        $data = $request->validate([
            'action' => ['required', 'in:pass,reject'],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        // 平台无机构上下文，可审核任意机构课程
        $course = Course::query()->where('merchant_id', '>', 0)->findOrFail($id);

        if ($data['action'] === 'pass') {
            $course->audit_status = Course::AUDIT_PASSED;
            $course->is_show = Course::IS_SHOW_YES; // 通过即上架
            $course->audit_remark = '';
        } else {
            $course->audit_status = Course::AUDIT_REJECTED;
            $course->is_show = Course::IS_SHOW_NO;
            $course->audit_remark = $data['remark'] ?? '';
        }
        $course->audited_at = Carbon::now();
        $course->audited_admin_id = (int)$this->admin()->id;
        $course->save();

        return $this->success();
    }

    /**
     * 机构端：提交课程审核(含被驳回后重新提交)。
     */
    public function submit($id)
    {
        $admin = $this->admin();
        $context = app(MerchantContext::class);
        if (!$admin || !$context->hasMerchant()) {
            abort(403, '只有机构管理员可以提交课程审核');
        }

        // 已被机构上下文自动作用域，只能提交本机构课程
        $course = Course::query()->findOrFail($id);
        $course->audit_status = Course::AUDIT_PENDING;
        $course->is_show = Course::IS_SHOW_NO;
        $course->submitted_at = Carbon::now();
        $course->audit_remark = '';
        $course->save();

        return $this->success();
    }
}
