<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Http\Controllers\Backend\Api\V2;

use App\Leedu\Leedu;
use App\Leedu\ServiceV2\Services\ConfigServiceInterface;

class SystemController extends BaseController
{
    public function config(ConfigServiceInterface $configService)
    {
        $config = [
            'video' => [
                'default_service' => $configService->getVideoDefaultService(),
            ],
            'system' => [
                //系统版本
                'version' => Leedu::VERSION,
                //logo
                'logo' => $configService->getLogo(),
                //访问地址
                'url' => [
                    'api' => $configService->getApiUrl(),
                    'pc' => $configService->getPCUrl(),
                    'h5' => $configService->getH5Url(),
                ],
            ],
        ];

        return $this->successData($config);
    }
}
