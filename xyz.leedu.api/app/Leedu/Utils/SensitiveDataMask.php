<?php

/*
 * This file is part of the Leedu.
 *
 * (c) 杭州白书科技有限公司
 */

namespace App\Leedu\Utils;

class SensitiveDataMask
{
    public static function valueMask(array $data, string $keyName)
    {
        foreach ($data as $key => $tmpItem) {
            if ($keyName === $key && $tmpItem) {
                $data[$key] = '*';
                continue;
            }
            if (is_array($tmpItem)) {
                $data[$key] = self::valueMask($tmpItem, $keyName);
            }
        }
        return $data;
    }
}
