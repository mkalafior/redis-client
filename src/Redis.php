<?php
namespace Redis;

use Redis\Connection\ConnectionInterface;

class Redis {

    protected $connection;

    public function __construct(ConnectionInterface $connectionStrategy) {
        $this->connection = $connectionStrategy;
    }

    public function read($key) {
        return $this->connection->read($key);
    }

    public function hmRead($key, $fields) {
        return $this->connection->hmRead($key, $fields);
    }

    public function hmWrite($key, $fields, $values) {
        return $this->connection->hmWrite($key, $fields, $values);
    }

    public function hmRemove($key, $fields) {
        return $this->connection->hmRemove($key, $fields);
    }

    public function hScan($key, $match) {
        return $this->connection->hScan($key, $match);
    }

    public function write($key, $value) {
       return $this->connection->write($key, $value);
    }

    public function multiCmd(array $cmd = array()) {
        return $this->connection->multiCmd($cmd);
    }
}