<?php

namespace Creativechain\Core;
use Creativechain\DB\Database;
use Creativechain\Util\Integers;
use Creativechain\Util\Sorter;
use Creativechain\Util\TxBuffer;
use Creativechain\Util\Util;


class Creativecoin {

    /** @var  RPCClient */
    private $rpcClient;

    /**
     * @var Database
     */
    private $database;

    /**
     * Creacoin constructor.
     */
    public function __construct()  {
        $this->rpcClient = new RPCClient();
        $this->database = new Database('./test.db');
    }

    public function getDataFromReference($ref) {

        $decoraw = $this->rpcClient->getTransaction($ref);
        $txdata = '';

        foreach($decoraw['vout'] as $key=>$value){

            if($value['scriptPubKey']['hex']){
                $txdata .= $value['scriptPubKey']['hex'];
            }
        }

        $txdata = strval(Util::hex2str($txdata));

        $txdata = explode("-CREA-", $txdata);

        $txids = json_decode($txdata[1]);
        $opdata = '';

        foreach($txids->txids as $key2=>$value2){

            $decoraw = $this->rpcClient->getTransaction($value2);

            foreach($decoraw['vout'] as $key3=>$value3){
                if($value3['scriptPubKey']['type'] == "nulldata"){
                    $opdataP = strval(Util::hex2str($value3['scriptPubKey']['hex']));
                    $opdataP = explode("-CREA-",$opdataP);
                    $opdata .= $opdataP[1];
                }
            }

        }

        $opdata=strval($opdata);

        return $opdata;
    }

    public function getTransactionData($txid) {

        $decoraw = $this->rpcClient->getTransaction($txid);
        $opdata = '';
        foreach($decoraw['vout'] as $key=>$value){
            if($value['scriptPubKey']['hex']){
                $opdataP = strval(Util::hex2str($value['scriptPubKey']['hex']));
                $opdataP = explode("-CREA-",$opdataP);
                $opdata .= $opdataP[1];
            }
        }

        $opdata = strval(Util::hex2str($opdata));

        $index = json_decode($opdata);

        $opdata = explode('-CREA-"', $opdata);

        return $opdata[1];
    }

    public function storeData($data)
    {
        /*
            Data is stored in OP_RETURNs within a series of chained transactions.
            The data is referred to by the txid of the first transaction containing an OP_RETURN.
            If the OP_RETURN is followed by another output, the data continues in the transaction spending that output.
            When the OP_RETURN is the last output, this also signifies the end of the data.
        */

        //	Validate parameters and get change address

        if (!$this->rpcClient->check())
            return array('error' => 'Please check Bitcoin Core is running and OP_RETURN_BITCOIN_* constants are set correctly');

        $data_len=strlen($data);
        if ($data_len == 0)
            return array('error' => 'Some data is required to be stored');

        //	Calculate amounts and choose first inputs to use

        $output_amount = BTC_FEE * ceil($data_len/MAX_BYTES); // number of transactions required
        $output_amount = $output_amount + (BTC_DUST * ceil($data_len/MAX_BYTES));
        $inputs_spend = $this->selectInputs($output_amount);
        if (isset($inputs_spend['error']))
            return $inputs_spend;

        $inputs = $inputs_spend['inputs'];
        $input_amount = $inputs_spend['total'];

        $height=$this->rpcClient->getBlockCount();
        $avoid_txids=$this->rpcClient->getRawMemPool();

        //	Loop to build and send transactions

        $result['txids']=array();

        for ($data_ptr = 0; $data_ptr<$data_len; $data_ptr+=MAX_BYTES) {
            $change_address =  $this->rpcClient->getRawChangeAddress();
            //	Some preparation for this iteration

            $last_txn = (($data_ptr+MAX_BYTES) >= $data_len); // is this the last tx in the chain?
            $change_amount = $input_amount-BTC_FEE;

            $metadata = substr($data, $data_ptr, MAX_BYTES-6);
            $metadata = "-CREA-".$metadata;

            //	Build and send this transaction

            $outputs = array();

            $outputs[$change_address] = $change_amount;

            $raw_txn =  $this->createTransaction($inputs, $outputs, $metadata, $last_txn ? count($outputs) : 0);

            $send_result =  $this->signAndSend($raw_txn);

            //	Check for errors and collect the txid

            if (isset($send_result['error'])) {
                $result['error'] = $send_result['error'];
                break;
            }

            $result['txids'][] = $send_result['txid'];
            sleep(1);
            if ($data_ptr == 0)
                $result['ref'] = $send_result['txid'];

            //	Prepare inputs for next iteration

            $inputs = array(array(
                'txid' => $send_result['txid'],
                'vout' => 1,
            ));

            $input_amount = $change_amount;
        }

        //	Return the final result

        return $result;
    }

    public function createTransaction($inputs, $outputs, $metadata, $metadata_pos) {

        $raw_txn =  $this->rpcClient->createRawTransaction($inputs, $outputs);

        $txn_unpacked =  $this->unpackTx(pack('H*', $raw_txn));

        $metadata_len = strlen($metadata);

        if ($metadata_len <= 75)
            $payload = chr($metadata_len).$metadata; // length byte + data (https://en.bitcoin.it/wiki/Script)
        elseif ($metadata_len <= 256)
            $payload = "\x4c".chr($metadata_len).$metadata; // OP_PUSHDATA1 format
        else
            $payload = "\x4d".chr($metadata_len%256).chr(floor($metadata_len/256)).$metadata; // OP_PUSHDATA2 format

        $metadata_pos = min(max(0, $metadata_pos), count($txn_unpacked['vout'])); // constrain to valid values

        array_splice($txn_unpacked['vout'], $metadata_pos, 0, array(array(
            'value' => BTC_DUST,
            'scriptPubKey' => '6a'.reset(unpack('H*', $payload)), // here's the OP_RETURN
        )));

        return reset(unpack('H*', $this->packTx($txn_unpacked)));
    }

    public function unpackTx($binary) {
        return $this->unpackTxBuffer(new TxBuffer($binary));
    }

    /**
     * @param TxBuffer $buffer
     * @return array
     */
    public function unpackTxBuffer($buffer) {
        // see: https://en.bitcoin.it/wiki/Transactions

        $txn = array();

        $txn['version'] = $buffer->shiftUnpack(4, 'V'); // small-endian 32-bits

        for ($inputs = $buffer->shiftVarInt(); $inputs>0; $inputs--) {
            $input = array();

            $input['txid'] = $buffer->shiftUnpack(32, 'H*', true);
            $input['vout'] = $buffer->shiftUnpack(4, 'V');
            $length = $buffer->shiftVarInt();
            $input['scriptSig'] = $buffer->shiftUnpack($length, 'H*');
            $input['sequence'] = $buffer->shiftUnpack(4, 'V');

            $txn['vin'][] = $input;
        }

        for ($outputs = $buffer->shiftVarInt(); $outputs>0; $outputs--) {
            $output = array();

            $output['value'] = $buffer->shiftUInt64()/100000000;
            $length = $buffer->shiftVarInt();
            $output['scriptPubKey'] = $buffer->shiftUnpack($length, 'H*');

            $txn['vout'][] = $output;
        }

        $txn['locktime'] = $buffer->shiftUnpack(4, 'V');

        return $txn;
    }

    /**
     * @param $unpackedTransaction
     * @return string
     */
    public function packTx($unpackedTransaction) {
        $binary = '';

        $binary .= pack('V', $unpackedTransaction['version']);

        $binary .= Integers::packVarInt(count($unpackedTransaction['vin']));

        foreach ($unpackedTransaction['vin'] as $input) {
            $binary .= strrev(pack('H*', $input['txid']));
            $binary .= pack('V', $input['vout']);
            $binary .= Integers::packVarInt(strlen($input['scriptSig'])/2); // divide by 2 because it is currently in hex
            $binary .= pack('H*', $input['scriptSig']);
            $binary .= pack('V', $input['sequence']);
        }

        $binary .= Integers::packVarInt(count($unpackedTransaction['vout']));

        foreach ($unpackedTransaction['vout'] as $output) {
            $binary .= Integers::packUInt64(round($output['value']*100000000));
            $binary .= Integers::packVarInt(strlen($output['scriptPubKey'])/2); // divide by 2 because it is currently in hex
            $binary .= pack('H*', $output['scriptPubKey']);
        }

        $binary .= pack('V', $unpackedTransaction['locktime']);

        return $binary;
    }

    public function signAndSend($raw_txn) {
        $signed_txn = $this->rpcClient->signRawTransaction($raw_txn);

        if (!$signed_txn['complete'])
            return array('error' => 'Could not sign the transaction');

        $send_txid = $this->rpcClient->sendRawTransaction($signed_txn['hex']);

        if (strlen($send_txid) != 64)
            return array('error' => 'Could not send the transaction txid:'.$send_txid.' raw : '.$send_txid);

        return array('txid' => $send_txid);
    }

    public function selectInputs($total_amount) {
        //	List and sort unspent inputs by priority

        $unspent_inputs = $this->rpcClient->listUnspent();

        if (!is_array($unspent_inputs))
            return array('error' => 'Could not retrieve list of unspent inputs');

        foreach ($unspent_inputs as $index => $unspent_input)
            $unspent_inputs[$index]['priority'] = $unspent_input['amount']*$unspent_input['confirmations'];
        // see: https://en.bitcoin.it/wiki/Transaction_fees

        Sorter::sortBy($unspent_inputs, 'priority');
        $unspent_inputs = array_reverse($unspent_inputs); // now in descending order of priority

        //	Identify which inputs should be spent

        $inputs_spend = array();
        $input_amount = 0;

        foreach ($unspent_inputs as $unspent_input) {
            $inputs_spend[] = $unspent_input;

            $input_amount+=$unspent_input['amount'];
            if ($input_amount >= $total_amount)
                break; // stop when we have enough
        }

        if ($input_amount<$total_amount)
            return array('error' => 'Not enough funds are available to cover the amount and fee');

        //	Return the successful result

        return array(
            'inputs' => $inputs_spend,
            'total' => $input_amount,
        );
    }

    public function getScriptData($scriptPubKeyBinary) {
        $op_return = null;

        if ($scriptPubKeyBinary[0] == "\x6a") {
            $first_ord = ord($scriptPubKeyBinary[1]);

            if ($first_ord <= 75)
                $op_return = substr($scriptPubKeyBinary, 2, $first_ord);
            elseif ($first_ord == 0x4c)
                $op_return = substr($scriptPubKeyBinary, 3, ord($scriptPubKeyBinary[2]));
            elseif ($first_ord == 0x4d)
                $op_return = substr($scriptPubKeyBinary, 4, ord($scriptPubKeyBinary[2])+256*ord($scriptPubKeyBinary[3]));
        }

        return $op_return;
    }

    public function findTransactionData($txn_unpacked) {
        foreach ($txn_unpacked['vout'] as $index => $output) {
            $op_return =  $this->getScriptData(pack('H*', $output['scriptPubKey']));

            if (isset($op_return))
                return array(
                    'index' => $index,
                    'op_return' => $op_return,
                );
        }

        return null;
    }

    public function findSpentTransactionId($txns, $spent_txid, $spent_vout) {
        foreach ($txns as $txid => $txn_unpacked)
            foreach ($txn_unpacked['vin'] as $input)
                if ( ($input['txid'] == $spent_txid) && ($input['vout'] == $spent_vout) )
                    return $txid;

        return null;
    }

    public function unpackBlock($binary) {
        $buffer = new TxBuffer($binary);
        $block = array();

        $block['version'] = $buffer->shiftUnpack(4, 'V');
        $block['hashPrevBlock'] = $buffer->shiftUnpack(32, 'H*', true);
        $block['hashMerkleRoot'] = $buffer->shiftUnpack(32, 'H*', true);
        $block['time'] = $buffer->shiftUnpack(4, 'V');
        $block['bits'] = $buffer->shiftUnpack(4, 'V');
        $block['nonce'] = $buffer->shiftUnpack(4, 'V');
        $block['tx_count'] = $buffer->shiftVarInt();

        $block['txs'] = array();

        $old_ptr = $buffer->used();

        while ($buffer->remaining()) {
            $transaction = $this->unpackTxBuffer($buffer);
            $new_ptr = $buffer->used();
            $size = $new_ptr-$old_ptr;

            $raw_txn_binary = substr($binary, $old_ptr, $size);
            $txid = reset(unpack('H*', strrev(hash('sha256', hash('sha256', $raw_txn_binary, true), true))));
            $old_ptr = $new_ptr;

            $transaction['size'] = $size;
            $block['txs'][$txid] = $transaction;
        }

        return $block;
    }

    public function matchRefTxId($ref, $txid) {
        $parts = $this->getReferenceParts($ref);
        if (!is_array($parts))
            return null;

        $txid_offset = floor($parts[1]/65536);
        $txid_binary = pack('H*', $txid);

        $txid_part = substr($txid_binary, 2*$txid_offset, 2);
        $txid_match = chr($parts[1]%256).chr(floor(($parts[1]%65536)/256));

        return $txid_part == $txid_match; // exact binary comparison
    }

    public function getReferenceParts($ref) {
        if (!preg_match('/^[0-9]+\-[0-9A-Fa-f]+$/', $ref)) // also support partial txid for second half
            return null;

        $parts = explode('-', $ref);

        if (preg_match('/[A-Fa-f]/', $parts[1])) {
            if (strlen($parts[1]) >= 4) {
                $txid_binary = hex2bin(substr($parts[1], 0, 4));
                $parts[1] = ord($txid_binary[0])+256*ord($txid_binary[1])+65536*0;
            } else
                return null;
        }

        if ($parts[1]>983039) // 14*65536+65535
            return null;

        return $parts;
    }

    public function getTryHeights($est_height, $max_height, $also_back) {
        $forward_height = $est_height;
        $back_height = min($forward_height-1, $max_height);

        $heights = array();
        $mempool = false;

        for ($try = 0; true; $try++) {
            if ($also_back && (($try%3) == 2)) { // step back every 3 tries
                $heights[] = $back_height;
                $back_height--;

            } else {
                if ($forward_height>$max_height) {
                    if (!$mempool) {
                        $heights[] = 0; // indicates to try mempool
                        $mempool = true;

                    } elseif (!$also_back)
                        break; // nothing more to do here

                } else
                    $heights[] = $forward_height;

                $forward_height++;
            }

            if (count($heights) >= MAX_BLOCKS)
                break;
        }

        return $heights;
    }

    public function getReferenceHeights($ref, $max_height) {
        $parts =  $this->getReferenceParts($ref);
        if (!is_array($parts))
            return null;

        return $this->getTryHeights((int)$parts[0], $max_height, true);
    }

    public function calcReference($next_height, $txid, $avoid_txids) {
        $txid_binary = pack('H*', $txid);

        $clashed = false;

        for ($txid_offset = 0; $txid_offset <= 14; $txid_offset++) {
            $sub_txid = substr($txid_binary, 2*$txid_offset, 2);


            foreach ($avoid_txids as $avoid_txid) {
                $avoid_txid_binary = pack('H*', $avoid_txid);

                if (
                    (substr($avoid_txid_binary, 2*$txid_offset, 2) == $sub_txid) &&
                    ($txid_binary != $avoid_txid_binary)
                ) {
                    $clashed = true;
                    break;
                }
            }

            if (!$clashed)
                break;
        }

        if ($clashed) // could not find a good reference
            return null;

        $tx_ref = ord($txid_binary[2*$txid_offset])+256*ord($txid_binary[1+2*$txid_offset])+65536*$txid_offset;

        return sprintf('%06d-%06d', $next_height, $tx_ref);
    }

    public function getRawBlock($height) {
        $block_hash = $this->rpcClient->getBlockHash($height);
        if (strlen($block_hash) != 64)
            return array('error' => 'Block at height '.$height.' not found');

        return array(
            'block' => pack('H*', $this->rpcClient->getBlock($block_hash))
        );
    }

    public function getTransactionForBlock($height) {
        $raw_block = $this->getRawBlock($height);

        if (isset($raw_block['error']))
            return array('error' => $raw_block['error']);

        $block = $this->unpackBlock($raw_block['block']);

        return $block['txs'];
    }

    public function creadeal($data) {
        $pubkeys = array();

        foreach($data['addr'] as $key=>$value){
            //echo $value;
            $rawtx =  $this->rpcClient->validateAddress($value);
            //print_r($rawtx);
            if($rawtx['isvalid'] == 1){

                $multisig[$value] = trim($rawtx['pubkey']);
                $pubkeys[] = $rawtx['pubkey'];
            }


        }
        $nsigns = $_POST['datos']['members'];

        $pubk = addslashes(json_encode($pubkeys));

        $args[0] = intval($nsigns);
        $args[1] = $pubkeys;
        $request = array(
            'id' => time().'-'.rand(100000,999999),
            'method' => "createmultisig",
            'params' => $args,
        );

        $port = BITCOIN_PORT;
        $user = BITCOIN_USER;
        $password = BITCOIN_PASSWORD;

        $curl = curl_init('http://'.BITCOIN_IP.':'.$port.'/');
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $user.':'.$password);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, NET_TIMEOUT_CONNECT);
        curl_setopt($curl, CURLOPT_TIMEOUT, NET_TIMEOUT_RECEIVE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
        $raw_result = curl_exec($curl);
        print_R($raw_result);
        return json_encode($raw_result);
    }

    public function explore() {

        echo "EXPLORING CREA BLOCKS .... SYNC ... please wait ... \n";

        $lastblock = $this->database->getAddressesTransactions(0, 1);

        print_r($lastblock);
        if(!empty($lastblock['block'])){

            $block = $lastblock['block'];

            $blocks =  $this->rpcClient->listSinceBlock($block);
            print_r($blocks);
        }else{
            $startHash = $this->rpcClient->getBlockHash(0);
            $blocks = $this->rpcClient->listSinceBlock($startHash);
        }

        print_r($blocks);
        foreach($blocks['transactions'] as $key=>$value){

            $txdata = $this->rpcClient->getTransaction($value['txid']);

            foreach($txdata['vout'] as $key2=>$value2){
                foreach($value2['scriptPubKey']['addresses'] as $key3=>$value3){
                    $this->database->addAddressTransaction($value3, $value['txid'], $value2['value'], $value['time'], $value['blockhash']);
                }
            }

            if(!empty($value['blockhash'])){
                $this->database->addAddressTransaction($value['address'], $value['txid'], $value['amount'], $value['time'], $value['blockhash']);

                $txdata = $this->getDataFromReference($value['txid']);

                $decodata = json_decode("[".$txdata."]");
                print_r($decodata);
                if(!empty($decodata[0]->contract)){
                    $this->database->addContractTransaction($decodata[0]->tx, $value['txid'], '', $value['time'], $decodata[0]->contract, $txdata);
                    print_R($decodata);
                }

            }

        }
    }

    /**
     * @param $tx
     * @return string
     */
    public function getTransaction($tx) {
        return $this->database->getTransaction($tx);
    }

    /**
     * @param $addr
     * @return string
     */
    public function getAddress($addr) {
        return $this->database->getTransactionsFromAddress($addr);
    }
}