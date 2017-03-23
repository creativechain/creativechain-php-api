<?php
/**
 * Created by PhpStorm.
 * User: ander
 * Date: 21/03/17
 * Time: 21:12
 */

namespace Creativecoin\Core;

define('BITCOIN_IP', '80.241.212.178'); // IP address of your bitcoin node
define('BITCOIN_USE_CMD', false); // use command-line instead of JSON-RPC?

if (BITCOIN_USE_CMD) {
    define('BITCOIN_PATH', '/home/creativechain2/src/Creativecoin'); // path to Creativecoin executable on this server

} else {
    define('BITCOIN_PORT', '19037'); // leave empty to use default port for mainnet/testnet
    define('BITCOIN_USER', 'Creativecoinrpc'); // leave empty to read from ~/.bitcoin/bitcoin.conf (Unix only)
    define('BITCOIN_PASSWORD', '7T4jXbct78WBvgujHM6Es5bEChwDooRqLH8FtpYmiiX'); // leave empty to read from ~/.bitcoin/bitcoin.conf (Unix only)
}


define('BTC_FEE', 0.004); // BTC fee to pay per transaction
define('BTC_DUST', 0.002); // omit BTC outputs smaller than this

define('MAX_BYTES', 1000); // maximum bytes in an OP_RETURN (40 as of Bitcoin 0.10)
define('MAX_BLOCKS', 10); // maximum number of blocks to try when retrieving data

define('NET_TIMEOUT_CONNECT', 5); // how long to time out when connecting to bitcoin node
define('NET_TIMEOUT_RECEIVE', 10); // how long to time out retrieving data from bitcoin node