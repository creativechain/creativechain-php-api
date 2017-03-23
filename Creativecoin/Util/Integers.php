<?php
/**
 * Created by PhpStorm.
 * User: ander
 * Date: 23/03/17
 * Time: 15:07
 */

namespace Creativecoin\Util;


class Integers
{

    /**
     * @param $integer
     * @return string
     */
    public static function packVarInt($integer) {
        if ($integer>0xFFFFFFFF)
            $packed="\xFF". Integers::packUInt64($integer);
        elseif ($integer>0xFFFF)
            $packed="\xFE".pack('V', $integer);
        elseif ($integer>0xFC)
            $packed="\xFD".pack('v', $integer);
        else
            $packed=pack('C', $integer);

        return $packed;
    }

    /**
     * @param $integer
     * @return string
     */
    public static function packUInt64($integer) {
        $upper=floor($integer/4294967296);
        $lower=$integer-$upper*4294967296;

        return pack('V', $lower).pack('V', $upper);
    }
}