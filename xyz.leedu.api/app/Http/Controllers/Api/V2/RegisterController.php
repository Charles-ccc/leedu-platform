<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Http\Controllers\Api\V2;

use App\Leedu\Visitor;
use App\Leedu\Merchant\CommissionService;
use App\Services\Member\Services\UserService;
use App\Http\Requests\ApiV2\RegisterSmsRequest;
use App\Services\Member\Interfaces\UserServiceInterface;

class RegisterController extends BaseController
{
    /**
     * @api {post} /api/v2/register/sms [V2]注册-短信
     * @apiGroup 用户认证
     * @apiName RegisterSMS
     *
     * @apiParam {String} mobile 手机号
     * @apiParam {String} mobile_code 短信验证码
     * @apiParam {String} password 密码
     *
     * @apiSuccess {Number} code 0成功,非0失败
     * @apiSuccess {Object} data 数据
     */
    public function smsHandler(RegisterSmsRequest $request, UserServiceInterface $userService, CommissionService $commissionService)
    {
        /**
         * @var UserService $userService
         */

        $this->mobileCodeCheck();

        ['mobile' => $mobile, 'password' => $password, 'promo_code' => $promoCode] = $request->filldata();

        // 平台化：新用户注册必须填写有效的业务员推广码
        $salesperson = $commissionService->findActiveByInviteCode((string)$promoCode);
        if (!$salesperson) {
            return $this->error(__('业务员推广码无效'));
        }

        if ($userService->findMobile($mobile)) {
            return $this->error(__('手机号已存在'));
        }

        $user = $userService->createWithMobile($mobile, $password, '', '', Visitor::data());

        // 绑定新用户到该业务员(永久)
        $commissionService->bindUser((int)$salesperson->id, (int)$user['id']);

        return $this->success();
    }
}
