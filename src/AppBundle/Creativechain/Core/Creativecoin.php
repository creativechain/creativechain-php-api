<?php
/**************************************************************
 * This Library has been created by :
 *      Vicent Nos @nOsDelAbismo
 *      Andersson Gabriel @ander7agar
 *      Sheila Mundo @sheilapimpampum
 **************************************************************/

namespace AppBundle\Creativechain\Core;

class Creativecoin{

    /** @var  RPCClient */
    private $rpcClient;

    /**
     * Creacoin constructor.
     */
    public function __construct()  {
        $this->rpcClient = new RPCClient();
        //$this->database = new Database(DATABASE_NAME);
    }
    public function getDataFromReference($ref) {

        $decoraw = $this->rpcClient->getTransaction($ref);
        $txdata = '';
        foreach($decoraw['result']['vout'] as $key=>$value){
            if($value['scriptPubKey']['hex']){
                $txdata .= $value['scriptPubKey']['hex'];
            }
        }
        $str = '';
        for($i=0;$i<strlen($txdata);$i+=2) $str .= chr(hexdec(substr($txdata,$i,2)));
        $txdata = $str;
        $txdata = explode("-CREA-", $txdata);
        $txids = $txdata[1];
        $opdata = '';
        $start = strpos($txids, '[');
        $finish = strpos($txids, ']');
        $sub = substr($txids,$start+1,$finish-10);
        $sub = explode(",",$sub);
        foreach($sub as $key2=>$value2){
            $value2= str_replace('"','',$value2);
            $decoraw = $this->rpcClient->getTransaction($value2);
            foreach($decoraw['result']['vout'] as $key3=>$value3){
                if($value3['scriptPubKey']['type'] == "nulldata"){
                    $str = '';
                    for($i=0;$i<strlen($value3['scriptPubKey']['hex']);$i+=2) $str .= chr(hexdec(substr($value3['scriptPubKey']['hex'],$i,2)));
                    $opdataP = $str;
                    $opdataP = explode("-CREA-",$opdataP);
                    $opdata .= $opdataP[1];
                }
            }

        }

        $opdata=strval($opdata);

        return $opdata;
    }
}