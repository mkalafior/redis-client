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

    public function read($key)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'read');
        }
        return $this->connection->read($key);
    }

    public function hmRead($key, $fields)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmRead');
        }
        return $this->connection->hmRead($key, $fields);
    }

    public function hmWrite($key, $fields, $values)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmWrite', 'fields' => $fields, 'values' => $values);
        }
        return $this->connection->hmWrite($key, $fields, $values);
    }

    public function hmRemove($key, $fields)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmRemove', 'fields' => $fields);
        }
        return $this->connection->hmRemove($key, $fields);
    }

    public function hmRemoveKey($key)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hmRemoveKey');
        }
        return $this->connection->hmRemove($key);
    }

    public function hScan($key, $match)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'hScan', 'match' => $match);
        }
        return $this->connection->hScan($key, $match);
    }

    public function write($key, $value)
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'method' => 'write', 'value' => $value);
        }
        return $this->connection->write($key, $value);
    }

    public function multiCmd(array $cmd = array())
    {
        if ($this->historyStatus) {
            foreach ($cmd as $c) {
                $cSplit = explode(" ", $c);
                $method = array_shift($cSplit);
                $key = array_shift($cSplit);
                $this->history[] = array('key' => $key, 'method' => $method, "arguments" => $cSplit, "multi" => true);
            }
        }
        return $this->connection->multiCmd($cmd);
    }

    public function toggleHistory()
    {
        return $this->historyStatus = !$this->historyStatus;
    }

    public function getCommandHistory()
    {
        return $this->history;
    }
}