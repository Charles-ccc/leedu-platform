<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 *
 * 平台化改造 M5：学员端 - 芝麻先享下单合同获取 + 勾选同意存证。
 */

namespace App\Http\Controllers\Api\V3;

use Illuminate\Http\Request;
use App\Leedu\Merchant\ZhimaService;
use App\Services\Course\Models\Course;
use App\Http\Controllers\Api\V2\BaseController;
use App\Leedu\ServiceV2\Models\AgreementTemplate;

class ZhimaController extends BaseController
{
    private function merchantIdOfCourse(int $courseId): int
    {
        $course = Course::withoutGlobalScopes()->find($courseId);
        return $course ? (int)$course->merchant_id : 0;
    }

    /**
     * 公开:获取某课程芝麻先享下单需展示的两份合同(网课买卖 + 芝麻代扣)。
     */
    public function contracts(Request $request, ZhimaService $zhima)
    {
        $courseId = (int)$request->input('course_id');
        $merchantId = $this->merchantIdOfCourse($courseId);

        $fmt = function (?AgreementTemplate $t) {
            return $t ? [
                'type' => $t->type,
                'version' => $t->version,
                'title' => $t->title,
                'content' => $t->content,
                'content_hash' => $t->content_hash,
            ] : null;
        };

        return $this->data([
            'course_sale' => $fmt($zhima->activeTemplate(AgreementTemplate::TYPE_COURSE_SALE, $merchantId)),
            'zhima_withhold' => $fmt($zhima->activeTemplate(AgreementTemplate::TYPE_ZHIMA_WITHHOLD, $merchantId)),
        ]);
    }

    /**
     * 登录:学员勾选同意合同 → 存证(append-only)。
     * body: course_id, types[] (course_sale/zhima_withhold)
     */
    public function consent(Request $request, ZhimaService $zhima)
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'min:1'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['in:course_sale,zhima_withhold'],
        ]);

        $merchantId = $this->merchantIdOfCourse((int)$data['course_id']);
        $userId = (int)$this->id();
        $ip = (string)$request->getClientIp();
        $ua = (string)$request->userAgent();

        $recorded = [];
        foreach (array_unique($data['types']) as $type) {
            $tpl = $zhima->activeTemplate($type, $merchantId);
            if (!$tpl) {
                return $this->error('合同未配置:' . $type);
            }
            $zhima->recordConsent($userId, 0, $merchantId, $tpl, $ip, $ua);
            $recorded[] = $type;
        }

        return $this->data(['consented' => $recorded]);
    }
}
