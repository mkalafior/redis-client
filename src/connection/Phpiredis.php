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

class Phpiredis implements ConnectionInterface
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
            $redis = phpiredis_connect('127.0.0.1', $port);
            $conn[$port] = $redis;
            self::$connections[$port] = $redis;
        }

        return $conn[$port] ?: false;
    }

    public function read($key)
    {
        $moved = false;

        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                throw new \Exception($errstr, $errno);
            }
        });
        if ($instance && $value = phpiredis_command_bs($instance, array('GET', $key))) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, array('GET', $key))) {
                restore_error_handler();
                return $value;
            }

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

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                restore_error_handler();
                throw new \Exception($errstr, $errno);
            }
        });

        if ($instance && $value = phpiredis_command_bs($instance, array('SET', $key, '' . $value))) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, array('SET', $key, '' . $value))) {
                restore_error_handler();
                return $value;
            }

        }

        return false;
    }

    public function hmRead($key, array $fields = array())
    {
        $moved = false;

        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        $tmp = array('HMGET', $key);
        while ($r = array_shift($fields)) {
            $tmp[] = $r;
        }

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if($msg[1] === 'MOVED'){
                $moved = $msg[2];
            }else{
                restore_error_handler();
                throw new \Exception($errstr, $errno);
            }
        });
        if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
                restore_error_handler();
                return $value;
            }

        }

        return false;
    }

    public function hmWrite($key, array $fields = array(), array $values = array())
    {

        $moved = false;

        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        $tmp = array('HMSET', $key);
        while ($r = array_shift($fields)) {
            $tmp[] = $r;
            $tmp[] = array_shift($values);
        }

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                restore_error_handler();
                throw new \Exception($errstr, $errno);
            }
        });

        if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
                restore_error_handler();
                return $value;
            }

        }

        return false;
    }

    public function hmRemove($key, array $fields = array())
    {
        $moved = false;

        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        $tmp = array('HDEL', $key);
        while ($r = array_shift($fields)) {
            $tmp[] = $r;
        }

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                restore_error_handler();
                throw new \Exception($errstr, $errno);
            }
        });
        if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
                restore_error_handler();
                return $value;
            }

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

    //todo
    public function getInstanceBySlotMap($key, $startingPort)
    {
        $slot = $this->getSlot($key);
        $instance = 0;
        return $this->connect($startingPort + $instance);
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

    protected function getSlotMap($startingPort)
    {
        $instance = $this->getInstanceByPort($startingPort);
        $resp = phpiredis_command_bs($instance, array('cluster', 'slots'));
        //todo:
    }

    public function hScan($key, $match)
    {
        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if($msg[1] === 'MOVED'){
                $moved = $msg[2];
            }else{
                restore_error_handler();
                throw new \Exception($errstr, $errno);
            }
        });

        $tmp = array('HSCAN', $key, 'MATCH', $match);
        if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
                restore_error_handler();
                return $value;
            }

        }
    }

    public function multiCmd (array $cmd = array()) {


        $instance = $this->getInstanceBySlot(
            0,
            $this->startingPort,
            $this->masterInstances
        );

        set_error_handler(function ($errno, $errstr) use (&$moved) {
            $msg = explode(" ", $errstr);
            if($msg[1] === 'MOVED'){
                $moved = $msg[2];
            }else{
                restore_error_handler();
                throw new \Exception($errstr, $errno);
            }
        });

        if ($instance && $value = phpiredis_command_bs($instance, $cmd)) {
            restore_error_handler();
            return $value;
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, $cmd)) {
                restore_error_handler();
                return $value;
            }

        }
    }

    public static function close()
    {
        $conn = self::$connections;
        foreach ($conn as $idx => $c) {
            unset(self::$connections[$idx]);
        }
    }
}