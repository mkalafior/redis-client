<?php
namespace Redis;

use Redis\Connection\ConnectionInterface;

class Redis
{

    protected $connection;

    public function __construct(ConnectionInterface $connectionStrategy)
    {
        $this->connection = $connectionStrategy;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function read($key)
    {
        return $this->connection->read($key);
    }

    /**
     * @param $key
     * @param $fields
     * @return mixed
     */
    public function hmRead($key, $fields)
    {
        return $this->connection->hmRead($key, $fields);
    }

    /**
     * @param $key
     * @param $fields
     * @param $values
     * @return mixed
     */
    public function hmWrite($key, $fields, $values)
    {
        return $this->connection->hmWrite($key, $fields, $values);
    }

    /**
     * @param $key
     * @param $fields
     * @return mixed
     */
    public function hmRemove($key, $fields)
    {
        return $this->connection->hmRemove($key, $fields);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hmRemoveKey($key)
    {
        return $this->connection->hmRemove($key);
    }

    /**
     * @param $key
     * @param $match
     * @return mixed
     */
    public function hScan($key, $match)
    {
        return $this->connection->hScan($key, $match);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function write($key, $value)
    {
        return $this->connection->write($key, $value);
    }

    /**
     * @param array $cmd
     * @return mixed
     */
    public function multiCmd(array $cmd = array())
    {
        return $this->connection->multiCmd($cmd);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function push($key, $value)
    {
        return $this->connection->push($key, $value);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function pop($key)
    {
        return $this->connection->pop($key);
    }


    public function getFullList($key, $remove = false)
    {
        return $this->connection->getFullList($key, $remove);
    }

    /**
     * @param $key
     * @param $list
     * @return mixed
     */
    public function pushFullList($key, $list) {
        return $this->connection->pushFullList($key, $list);
    }
}