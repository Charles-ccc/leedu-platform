<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu;

use Illuminate\Contracts\Foundation\Application;

class AddonsProvider
{
    /**
     * @param Application $app
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function bootstrap(Application $app)
    {
        $leeduAddons = new Addons();
        $providers = $leeduAddons->getProvidersMap();
        if ($providers) {
            array_map(function ($provider) use ($app) {
                $app->register($provider);
            }, $providers);
        }
    }
}
