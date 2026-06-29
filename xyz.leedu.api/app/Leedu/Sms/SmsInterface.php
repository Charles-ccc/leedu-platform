<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Sms;

interface SmsInterface
{
    public function sendCode(string $mobile, $code, $template);
}
