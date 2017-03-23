<?php
/**
 * Created by PhpStorm.
 * User: ander
 * Date: 23/03/17
 * Time: 15:52
 */

namespace Creativecoin\Util;


class Sorter
{

    public static function sortBy(&$array, $by1, $by2=null) {
        global $sort_by_1, $sort_by_2;

        $sort_by_1=$by1;
        $sort_by_2=$by2;

        uasort($array, 'Sorter::sortByFN');
    }

    public static function sortByFN($a, $b) {
        global $sort_by_1, $sort_by_2;

        $compare= Sorter::sortCMP($a[$sort_by_1], $b[$sort_by_1]);

        if (($compare==0) && $sort_by_2)
            $compare= Sorter::sortCMP($a[$sort_by_2], $b[$sort_by_2]);

        return $compare;
    }

    public static function sortCMP($a, $b) {
        if (is_numeric($a) && is_numeric($b)) // straight subtraction won't work for floating bits
            return ($a==$b) ? 0 : (($a<$b) ? -1 : 1);
        else
            return strcasecmp($a, $b); // doesn't do UTF-8 right but it will do for now
    }
}