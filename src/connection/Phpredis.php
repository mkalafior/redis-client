<?php
/**
 * Created by PhpStorm.
 * User: sebakpl
 * Date: 21/03/15
 * Time: 12:39
 */

namespace Redis\Connection;

include_once('ConnectionInterface.php');

use Redis\Algorithms;

class Phpredis implements ConnectionInterface
{

    protected static $connections = array();
    protected $hashingInterface;
    protected $startingPort;
    protected $masterInstances;


    public function __construct(Algorithms\AlgorithmsInterface $hashingInterface, $startingPort, $masterInstances)
    {
        $this->hashingInterface = $hashingInterface;
        $this->startingPort = $startingPort;
        $this->masterInstances = $masterInstances;
    }

    public function connect($port)
    {
        $conn = self::$connections;

        if (!isset($conn[$port])) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', $port);
            $conn[$port] = $redis;
            self::$connections[$port] = $redis;
        }

        return $conn[$port] ?: false;
    }

    public function read($key)
    {
        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        $maxPort = $this->startingPort + $this->masterInstances;
        $port = $this->startingPort;

        if ($instance && $value = $instance->get('GET', $key)) {
            return $value;
        }

        while ($port < $maxPort) {
            $redis = $this->getInstanceByPort($port);
            if ($redis && $value = $redis->get($key)) {
                return $value;
            }
            $port++;
        }

        return false;
    }

    public function write($key, $value)
    {
        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        if ($instance && $response = $instance->set($key, $value)) {
            return true;
        }

        $maxPort = $this->startingPort + $this->masterInstances;
        $port = $this->startingPort;

        while ($port < $maxPort) {
            $redis = $this->getInstanceByPort($port);
            if ($redis && $redis->set($key, $value)) {
                return true;
            }
            $port++;
        }

        return false;
    }

    public function getInstanceBySlot($slot, $startingPort, $masterInstances)
    {
        $instance = floor(($slot % 16384) / (16384 / $masterInstances));
        return $this->connect($startingPort + $instance);
    }

    public function getInstanceByPort($port)
    {
        return $this->connect($port);
    }

    public function getSlot($key)
    {

        if (false !== $start = strpos($key, '{')) {
            if (false !== ($end = strpos($key, '}', $start)) && $end !== ++$start) {
                $key = substr($key, $start, $end - $start);
            }
        }
        $hash = $this->hashingInterface->hash($key);
        return $hash & 0x3FFF;
    }

    public function hmRead($key, array $fields = array())
    {
        // TODO: Implement hmRead() method.
    }

    public function hmWrite($key, array $fields = array(), array $values = array())
    {
        // TODO: Implement hmWrite() method.
    }

    public function hScan($key, $match)
    {
        // TODO: Implement hScan() method.
    }

    public function multiCmd(array $cmd = array())
    {
        // TODO: Implement multiCmd() method.
    }

    public function hmRemove($key, array $fields = array())
    {
        // TODO: Implement hmRemove() method.
    }

}