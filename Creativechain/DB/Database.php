<?php

namespace Creativechain\DB;

use SQLite3;
use SQLite3Result;

class Database {

    private $dbName;

    /** @var  SQLite3 */
    private $sqlite;


    /**
     * Database constructor.
     * @param $dbName
     */
    public function __construct($dbName)
    {
        $this->dbName = $dbName;
        $this->create();
    }


    private function create() {
        $this->sqlite = new SQLite3($this->dbName);

        $sql = array();
        $sql[0] = 'CREATE TABLE IF NOT EXISTS addrtotx (addr TEXT NOT NULL, tx TEXT NOT NULL, amount INTEGER NOT NULL, att_date INTEGER NOT NULL, block TEXT NOT NULL, PRIMARY KEY (addr, tx));';
        $sql[1] = 'CREATE TABLE IF NOT EXISTS contracttx (ctx TEXT NOT NULL, ntx TEXT NOT NULL, addr TEXT NOT NULL, ct_date INTEGER NOT NULL, type TEXT NOT NULL, c_data TEXT NOT NULL, PRIMARY KEY (ctx, ntx))';
        $sql[2] = 'CREATE TABLE IF NOT EXISTS phptracker_peers (peer_id TEXT NOT NULL, ip_address INTEGER NOT NULL, port INTEGER NOT NULL, info_hash TEXT NOT NULL, bytes_uploaded INTEGER DEFAULT NULL, bytes_downloaded INTEGER DEFAULT NULL, bytes_left INTEGER DEFAULT NULL, status TEXT DEFAULT "incomplete", expires INTEGER DEFAULT NULL, PRIMARY KEY (peer_id, info_hash))';
        $sql[3] = 'CREATE TABLE IF NOT EXISTS phptracker_torrents (info_hash INTEGER NOT NULL, lenght INTEGER NOT NULL, pieces_lenght INTEGER NOT NULL, name TEXT, pieces TEXT NOT NULL, path TEXT NOT NULL, status TEXT NOT NULL DEFAULT "active", PRIMARY KEY (info_hash))';
        $sql[4] = 'CREATE TABLE IF NOT EXISTS txToReference (ref TEXT NOT NULL, tx TEXT NOT NULL, ttr_date INTEGER NOT NULL, PRIMARY KEY (ref, tx))';
        $sql[5] = 'CREATE TABLE IF NOT EXISTS wordPoints (wordHash TEXT NOT NULL, points INTEGER NOT NULL, PRIMARY KEY (wordHash))';
        $sql[5] = 'CREATE TABLE IF NOT EXISTS wordToReference (wordHash TEXT NOT NULL, ref TEXT NOT NULL, b_date INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP, wtr_order INTEGER NOT NULL, PRIMARY KEY (wordHash))';
        $sql[6] = 'CREATE INDEX index_addrtotx_addr ON addrtotx (addr);';
        $sql[7] = 'CREATE INDEX index_addrtotx_tx ON addrtotx (tx);';
        $sql[8] = 'CREATE INDEX index_contracttx_ctx ON contracttx (ctx);';
        $sql[9] = 'CREATE INDEX index_contracttx_ntx ON contracttx (ntx);';
        foreach ($sql as $s) {
            $this->sqlite->exec($s);
        }
    }

    public function getTransaction($tx) {
        $query = "SELECT * FROM addrtotx WHERE tx = '".addslashes($tx)."' ORDER BY att_date DESC";
        return $this->executeToJson($query);
    }

    public function getTransactionsFromAddress($addr) {
        $query = "SELECT * FROM addrtotx WHERE addr = '".addslashes($addr)."' ORDER BY att_date DESC";
        return $this->executeToJson($query);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAddressesTransactions($limit = 0, $offset = 0) {
        $query = "SELECT * FROM addrtotx ORDER BY att_date DESC";

        if ($limit > 0) {
            $query = $query . " LIMIT " . $limit . ", " . $offset;
        }

        $query = $query . ";";

        return $this->executeToArray($query);
    }

    public function addAddressTransaction($address, $tx, $amount, $date, $block) {
        $query = "INSERT INTO addrtotx (addr, tx, amount, att_date, block) VALUES ('". $address ."', '". $tx ."', '". $amount ."', '". $date ."', '". $block ."');";
        $this->executeQuery($query);
    }

    public function addContractTransaction($ctx, $ntx, $addr, $date, $type, $data) {
        $query = "INSERT INTO contracttx (ctx, ntx, addr, ct_date, type, data) VALUES ('". $ctx."', '". $ntx ."', '" . $addr . "', '". $date ."', '" . $type . "', '". $data."');";
        $this->executeQuery($query);
    }

    /**
     * @param $query
     * @return SQLite3Result
     */
    public function executeQuery($query) {
        /** @var SQLite3Result $result */
        $result = $this->sqlite->query($query);
        return $result;
    }

    /**
     * @param $query
     * @return array
     */
    public function executeToArray($query) {
        /** @var SQLite3Result $result */
        $result = $this->executeQuery($query);
        return $result->fetchArray();
    }

    /**
     * @param $query
     * @return string
     */
    public function executeToJson($query) {
        return json_encode($this->executeToArray($query));
    }
}
