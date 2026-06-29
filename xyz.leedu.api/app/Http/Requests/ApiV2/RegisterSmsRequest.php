<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Http\Requests\ApiV2;

class RegisterSmsRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'mobile' => 'required',
            'mobile_code' => 'required',
            'password' => 'required|min:6|max:16',
            'promo_code' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'mobile.required' => __('请输入手机号'),
            'mobile_code.required' => __('请输入短信验证码'),
            'password.required' => __('请输入密码'),
            'password.min' => __('密码长度不能少于:size个字符', ['size' => 6]),
            'password.max' => __('密码长度不能多于:size个字符', ['size' => 16]),
            'promo_code.required' => __('请输入业务员推广码'),
        ];
    }

    public function filldata()
    {
        return [
            'mobile' => $this->input('mobile'),
            'mobile_code' => $this->input('mobile_code'),
            'password' => $this->input('password'),
            'promo_code' => $this->input('promo_code'),
        ];
    }
}
