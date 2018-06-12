<?php
namespace Redis;

use Redis\Connection\ConnectionInterface;

/**
 * Class Redis
 * @package Redis
 */
class Redis
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var bool
     */
    protected $historyStatus = false;
    /**
     * @var array
     */
    protected $history = array();

    /**
     * @param ConnectionInterface $connectionStrategy
     */
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
            $this->history[] = array('key' => $key, 'fields' => $fields, 'method' => 'hmRead', 'arguments' => array($fields));
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
            $this->history[] = array('key' => $key, 'fields' => $fields, 'method' => 'hmWrite', 'arguments' => array($fields, $values));
        }
        return $this->connection->hmWrite($key, $fields, $values);
    }

    /**
     * @param $key
     * @param $fields
     * @return mixed
     */
    public function hmRemove($key, $fields = array())
    {
        if ($this->historyStatus) {
            $this->history[] = array('key' => $key, 'fields' => $fields, 'method' => 'hmRemove', 'arguments' => array($fields));
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
     * @param $cacheTime
     * @return mixed
     */
    public function write($key, $value, $cacheTime = false)
    {
        if ($this->historyStatus) {
            $arguments = array($value);

            if ($cacheTime) {
                array_push($arguments, 'EX');
                array_push($arguments, $cacheTime);
            }

            $this->history[] = array('key' => $key, 'method' => 'write', 'arguments' => $arguments);
        }
        return $this->connection->write($key, $value, $cacheTime);
    }

    /**
     * @param $cmd
     * @return mixed
     */
    public function singleCmd($cmd)
    {
        if ($this->historyStatus) {
            $tmpCmd = $cmd;
            $method = array_shift($tmpCmd);
            $key = array_shift($tmpCmd);
            $this->history[] = array('key' => $key, 'method' => $method, 'arguments' => array($tmpCmd), 'singleCmd' => true);

        }
        return $this->connection->singleCmd($cmd);
    }

    /**
     * @param array $cmd
     * @return mixed
     */
    public function multiCmd(array $cmd = array())
    {
        if ($this->historyStatus) {
            if ($this->historyStatus) {
                foreach ($cmd as $c) {
                    $tmpCmd = $c;
                    $method = array_shift($tmpCmd);
                    $key = array_shift($tmpCmd);
                    $this->history[] = array('key' => $key, 'method' => $method, 'arguments' => array($tmpCmd), 'multiCmd' => true);
                }
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
     * @return bool
     */
    public function turnOnHistory()
    {
        return $this->historyStatus = true;
    }

    /**
     * @return bool
     */
    public function turnOffHistory()
    {
        return $this->historyStatus = false;
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
            $this->history[] = array('key' => $key, 'method' => 'del');
            $this->history[] = array('key' => $key, 'method' => 'lpush', 'values'=>$list);
        }
        return $this->connection->pushFullList($key, $list);
    }

    public function info($section = '')
    {
        return $this->connection->info($section);
    }

    public function keys()
    {
        return $this->connection->keys();
    }
}