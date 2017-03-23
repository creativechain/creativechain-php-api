<?php

namespace Creativechain\Core;


const GET_DATA_FROM_REF = 'getdatafromref';
const GET_DATA_TX = 'getdatatx';
const DATA_STORE = 'datastore';
const EXPLORE = 'explore';

$creativecoin = new Creativecoin();

if(!empty($argv[1])){
    switch ($argv[1]) {
        case GET_DATA_FROM_REF:
            echo $creativecoin->getDataFromReference($argv[2]);
            break;
        case GET_DATA_TX:
            echo $creativecoin->getTransactionData($argv[2]);
            break;
        case DATA_STORE:
            print_r($creativecoin->storeData($argv[2]));
            break;
        case EXPLORE:
            $creativecoin->explore();
            break;
        default:

    }
}

if(!empty($_GET['block'])){
    $creativecoin->explore();
}

if(!empty($_GET['tx'])){
    print_r($creativecoin->getTransaction($_GET['tx']));
}

if(!empty($_GET['addr'])){
    print_r($creativecoin->getAddress($_GET['addr']));
}
