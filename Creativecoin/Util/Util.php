<?php
/**
 * Created by PhpStorm.
 * User: ander
 * Date: 23/03/17
 * Time: 16:31
 */

namespace Creativecoin\Util;


class Util {

    public static function hex2str($hex) {
        $str = '';
        for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
        return $str;
    }
}