<?php

namespace Creativecoin\Util;
/**
 * Created by PhpStorm.
 * User: ander
 * Date: 23/03/17
 * Time: 14:58
 */
class TxBuffer {

    private $data;
    private $len;
    private $ptr;

    function __construct($data, $ptr = 0) {
        $this->data=$data;
        $this->len=strlen($data);
        $this->ptr=$ptr;
    }

    /**
     * @param $chars
     * @return string
     */
    function shift($chars) {
        $prefix=substr($this->data, $this->ptr, $chars);
        $this->ptr+=$chars;

        return $prefix;
    }

    /**
     * @param $chars
     * @param $format
     * @param bool $reverse
     * @return mixed
     */
    function shiftUnpack($chars, $format, $reverse=false) {
        $data=$this->shift($chars);
        if ($reverse)
            $data=strrev($data);

        $unpack=unpack($format, $data);

        return reset($unpack);
    }

    /**
     * @return mixed
     */
    function shiftVarInt() {
        $value=$this->shiftUnpack(1, 'C');

        if ($value==0xFF)
            $value=$this->shiftUInt64();
        elseif ($value==0xFE)
            $value=$this->shiftUnpack(4, 'V');
        elseif ($value==0xFD)
            $value=$this->shiftUnpack(2, 'v');

        return $value;
    }

    /**
     * @return mixed
     */
    function shiftUInt64() {
        return $this->shiftUnpack(4, 'V')+($this->shiftUnpack(4, 'V')*4294967296);
    }

    /**
     * @return mixed
     */
    function used() {
        return min($this->ptr, $this->len);
    }

    /**
     * @return mixed
     */
    function remaining() {
        return max($this->len-$this->ptr, 0);
    }
}