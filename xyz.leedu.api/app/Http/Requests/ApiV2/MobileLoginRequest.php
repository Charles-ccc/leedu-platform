<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Http\Requests\ApiV2;

class MobileLoginRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'mobile' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'mobile.required' => __('请输入手机号'),
        ];
    }

    public function filldata()
    {
        return [
            'mobile' => $this->post('mobile'),
            // 仅新用户(手机号登录即自动注册)时需要,老用户登录可不填
            'promo_code' => $this->post('promo_code'),
        ];
    }
}
