<?php
namespace Redis;

use Redis\Connection\ConnectionInterface;

class Redis
{

    protected $connection;
    protected $historyStatus = false;
    protected $history = array();

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
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'read');
        }
        return $this->connection->read($key);
    }

    /**
     * @param $key
     * @param $fields
     * @return mixed
     */
    public function hmRead($key, $fields)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmRead', 'arguments' => array($fields));
        }
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
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmWrite', 'arguments' => array($fields, $values));
        }
        return $this->connection->hmWrite($key, $fields, $values);
    }

    /**
     * @param $key
     * @param $fields
     * @return mixed
     */
    public function hmRemove($key, $fields)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmRemove', 'arguments' => array($fields));
        }
        return $this->connection->hmRemove($key, $fields);
    }


    /**
     * @param $key
     * @return mixed
     */
    public function hmRemoveKey($key)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmRemoveKey');
        }
        return $this->connection->hmRemove($key);
    }

    /**
     * @param $key
     * @param $match
     * @return mixed
     */
    public function hScan($key, $match)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hScan', 'arguments' => array($match));
        }
        return $this->connection->hScan($key, $match);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function write($key, $value)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'write', 'arguments' => array($value));
        }
        return $this->connection->write($key, $value);
    }

    /**
     * @param array $cmd
     * @return mixed
     */
    public function multiCmd(array $cmd = array())
    {
        if ($this->historyStatus) {
            foreach ($cmd as $c) {
                $cSplit = explode(" ", $c);
                $method = array_shift($cSplit);
                $key = array_shift($cSplit);
                $this->history[] = array('key' => $key, 'method' => $method, 'arguments' => array($cSplit), 'multiCmd' => true);
            }
        }
        return $this->connection->multiCmd($cmd);
    }

    /**
     * @return bool
     */
    public function toggleHistory()
    {
        return $this->historyStatus = !$this->historyStatus;
    }

    /**
     * @param $clear
     * @return array
     */
    public function getCommandHistory($clear = false)
    {
        if ($clear) {
            $history = $this->history;
            $this->history = array();
        } else {
            $history = $this->history;
        }
        return $history;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function push($key, $value)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'push', 'arguments' => array($value));
        }
        return $this->connection->push($key, $value);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function pop($key)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'pop');
        }
        return $this->connection->pop($key);
    }


    /**
     * @param $key
     * @param bool $remove
     * @return mixed
     */
    public function getFullList($key, $remove = false)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'getFullList', 'arguments' => array($remove));
        }
        return $this->connection->getFullList($key, $remove);
    }

    /**
     * @param $key
     * @param $list
     * @return mixed
     */
    public function pushFullList($key, $list)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'pushFullList', 'arguments' => array($list));
        }
        return $this->connection->pushFullList($key, $list);
    }

    public function info($section) {
        return $this->connection->info($section);
    }
}