<?php

namespace Creativechain\Core;

class RPCClient
{

    private $user;
    private $password;
    private $port;
    private $ip;

    /**
     * RPCClient constructor.
     */
    public function __construct() {
        $this->port = BITCOIN_PORT;
        $this->user = BITCOIN_USER;
        $this->password = BITCOIN_PASSWORD;
        $this->ip = BITCOIN_IP;
    }

    public function getTransaction($txHash) {
        $result = $this->getRawTransaction($txHash);
        return $this->decodeRawTransaction($result['hex']);
    }

    public function getRawTransaction($txHash) {
        return $this->buildExecution('gettransaction', array($txHash));
    }

    public function decodeRawTransaction($txHex) {
        return $this->buildExecution('decoderawtransaction', array($txHex));
    }

    public function listSinceBlock($block) {
        return $this->buildExecution('listsinceblock', array($block));
    }

    public function getBlockHash($height) {
        return $this->buildExecution('getblockhash', array($height));
    }

    public function getInfo() {
        return $this->buildExecution('getinfo', array());
    }

    public function getBlockCount() {
        return $this->buildExecution('getblockcount', array());
    }

    public function getRawMemPool() {
        return $this->buildExecution('getrawmempool', array());
    }

    public function getRawChangeAddress() {
        return $this->buildExecution('getrawchangeaddress', array());
    }

    public function createRawTransaction($inputs, $outputs) {
        return $this->buildExecution('createrawtransaction', array($inputs, $outputs));
    }

    public function signRawTransaction($rawTransaction) {
        return $this->buildExecution('signrawtransaction', array($rawTransaction));
    }

    public function sendRawTransaction($rawTransaction) {
        return $this->buildExecution('sendrawtransaction', array($rawTransaction));
    }

    public function listUnspent() {
        return $this->buildExecution('listunspent', array(0));
    }

    public function getBlock($blockHash) {
        return $this->buildExecution('getblock', array($blockHash, false));
    }

    public function validateAddress($address) {
        return $this->buildExecution('validateaddress', array($address));
    }

    /**
     * @return bool
     */
    public function check() {
        $result = $this->getInfo();
        return is_array($result);
    }

    /**
     * @param string $command
     * @param array $args
     * @return mixed
     */
    private function buildExecution($command, array $args) {
        return $this->execute(array(
            'id' => time().'-'.rand(100000,999999),
            'method' => $command,
            'params' => $args,
        ));
    }

    /**
     * @param array $request
     * @return mixed
     */
    private function execute(array $request) {
        $curl=curl_init('http://'.$this->ip.':'.$this->port.'/');
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, NET_TIMEOUT_CONNECT);
        curl_setopt($curl, CURLOPT_TIMEOUT, NET_TIMEOUT_RECEIVE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
        $raw_result=curl_exec($curl);
        curl_close($curl);

        return json_decode($raw_result, true);
    }

}