<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace Tests\Commands;

use Tests\OriginalTestCase;

class LeeduUpgradeCommandTest extends OriginalTestCase
{
    public function test_upgrade()
    {
        $this->artisan('leedu:upgrade')->assertSuccessful();
    }
}
