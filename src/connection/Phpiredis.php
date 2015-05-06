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

/**
 * Class Phpiredis
 * @package Redis\Connection
 */
class Phpiredis implements ConnectionInterface
{

    /**
     * @var array
     */
    protected static $connections = array();
    /**
     * @var Algorithms\AlgorithmsInterface
     */
    protected $hashingInterface;
    /**
     * @var
     */
    protected $startingPort;
    /**
     * @var
     */
    protected $masterInstances;

    /**
     * @param $value
     * @return bool
     */
    private function checkIfMoved($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $msg = explode(" ", $value);

        if ($msg[0] === 'MOVED') {
            $msg = explode(':', $msg[2]);
            return $msg[1];
        }

        return false;
    }

    /**
     * @param $instance
     * @param array $cmdRecs
     * @return mixed
     */
    private function singleMultiCmd($instance, $cmdRecs = array())
    {
        $moved = false;

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            restore_error_handler();
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
                die();
            }
        });

        $order = array();
        $cmd = array();
        foreach ($cmdRecs as $r) {
            $order[] = $r['order'];
            $cmd[] = $r['cmd'];
        }

        $sort = function ($values) use ($order) {
            $return = array();

            foreach ($values as $idx => $value) {
                $return[$order[$idx]] = $value;
            }

            return $return;
        };

        if ($instance && $value = phpiredis_multi_command_bs($instance, $cmd)) {
            if (isset($value[0])) {
                $moved = $this->checkIfMoved($value[0]);
            }

            if (!$moved) {
                restore_error_handler();
                return $sort($value);
            }
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_multi_command_bs($instance, $cmd)) {
                restore_error_handler();
                return $sort($value);
            }

        }
    }

    /**
     * @param Algorithms\AlgorithmsInterface $hashingInterface
     * @param $startingPort
     * @param $masterInstances
     */
    public function __construct(Algorithms\AlgorithmsInterface $hashingInterface, $startingPort, $masterInstances)
    {
        $this->hashingInterface = $hashingInterface;
        $this->startingPort = $startingPort;
        $this->masterInstances = $masterInstances;
    }

    /**
     * @param $port
     * @return bool
     */
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

    /**
     * @param $key
     * @return bool
     */
    public function read($key)
    {
        $moved = false;

        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
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

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function write($key, $value)
    {
        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
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

    /**
     * @param $key
     * @param array $fields
     * @return bool
     */
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

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
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

    /**
     * @param $key
     * @param array $fields
     * @param array $values
     * @return bool
     */
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


        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
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

    /**
     * @param $key
     * @param array $fields
     * @return bool
     */
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

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
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

    /**
     * @param $slot
     * @param $startingPort
     * @param $masterInstances
     * @return bool
     */
    public function getInstanceBySlot($slot, $startingPort, $masterInstances)
    {
        $instance = floor(($slot % 16384) / (16384 / $masterInstances));
        return $this->connect($startingPort + $instance);
    }

    /**
     * @param $slot
     * @param $startingPort
     * @param $masterInstance
     * @return mixed
     */
    public function getPortBySlot($slot, $startingPort, $masterInstance)
    {
        return $startingPort + floor(($slot % 16384) / (16384 / $masterInstance));
    }

    /**
     * @param $port
     * @return bool
     */
    public function getInstanceByPort($port)
    {
        return $this->connect($port);
    }

    /**
     * @param $key
     * @param $startingPort
     * @return bool
     */
    public function getInstanceBySlotMap($key, $startingPort)
    {
        $slot = $this->getSlot($key);
        //todo
        $instance = 0;
        return $this->connect($startingPort + $instance);
    }

    /**
     * @param $key
     * @return int
     */
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

    /**
     * @param $startingPort
     */
    protected function getSlotMap($startingPort)
    {
        $instance = $this->getInstanceByPort($startingPort);
        $resp = phpiredis_command_bs($instance, array('cluster', 'slots'));
        //todo:
    }

    /**
     * @param $key
     * @param $match
     * @return mixed
     */
    public function hScan($key, $match)
    {
        $instance = $this->getInstanceBySlot(
            $this->getSlot($key),
            $this->startingPort,
            $this->masterInstances
        );

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$moved) {
            $msg = explode(" ", $errstr);
            if ($msg[1] === 'MOVED') {
                $moved = $msg[2];
            } else {
                echo "\r\n// " . join(", ", array($errstr, 0, $errno, $errfile, $errline));
            }
        });

        $tmp = array('HSCAN', $key, 'MATCH', $match);
        if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {
            if (isset($value)) {
                $moved = $this->checkIfMoved($value[0]);
            }

            if (!$moved) {
                restore_error_handler();
                return $value;
            }
        }

        if ($moved) {

            $instance = $this->getInstanceByPort($moved);
            if ($instance && $value = phpiredis_command_bs($instance, $tmp)) {

                restore_error_handler();
                return $value;
            }

        }
    }

    /**
     * @param array $cmd
     * @return array
     */
    public function multiCmd(array $cmd = array())
    {
        $instances = array();
        $values = array();

        $order = 0;
        foreach ($cmd as $c) {
            $key = $c[1];
            $slot = $this->getSlot($key);
            $port = $this->getPortBySlot($slot, $this->startingPort, $this->masterInstances);
            $instances[$port] = isset($instances[$port]) ? $instances[$port] : array();
            $instances[$port][] = array('order' => $order++, 'cmd' => $c);
        }

        foreach ($instances as $port => $_cmd) {
            $instance = $this->getInstanceByPort($port);
            $tmp = $this->singleMultiCmd($instance, $_cmd);
            $values = array_merge($values, $tmp);
        }

        return $values;
    }

    /**
     *
     */
    public static function close()
    {
        $conn = self::$connections;
        foreach ($conn as $idx => $c) {
            unset(self::$connections[$idx]);
        }
    }
}