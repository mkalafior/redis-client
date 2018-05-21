<?php
/**
 * Created by PhpStorm.
 * User: arek
 * Date: 21/03/15
 * Time: 12:39
 */

namespace Redis\Connection;


/**
 * Class Phpiredis
 * @package Redis\Connection
 */
class PhpiredisMock implements ConnectionInterface
{

    public function __construct(){
    }

    public function singleCmd(array $cmd) {
        return false;
    }


    /**
     * @param $port
     * @return bool
     */
    public function connect($port) {
        return false;
    }

    /**
     * @param $key
     * @return bool
     */
    public function read($key){
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @param $cacheTime
     * @return bool
     */
    public function write($key, $value, $cacheTime = false)
    {
        return 'OK';
    }

    /**
     * @param $key
     * @param array $fields
     * @return bool
     */
    public function hmRead($key, array $fields = array())
    {

        return 'OK';
    }

    /**
     * @param $key
     * @param array $fields
     * @param array $values
     * @return bool
     */
    public function hmWrite($key, array $fields = array(), array $values = array())
    {

        return 'OK';
    }

    /**
     * @param $key
     * @param array $fields
     * @return bool
     */
    public function hmRemove($key, array $fields = array())
    {

        return 'OK';
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
     * @param $key
     * @param $match
     * @return mixed
     */
    public function hScan($key, $match)
    {
        return 'OK';
    }

    /**
     * @param array $cmd
     * @return array
     */
    public function multiCmd(array $cmd = array())
    {
        return [];
    }

    /**
     *
     */
    public static function close()
    {
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function push($key, $value)
    {
        $slot = $this->getSlot($key);
        $port = $this->getPortBySlot($slot, $this->startingPort, $this->masterInstances);
        $instance = $this->getInstanceByPort($port);
        return $this->_singleCmd($instance, array("LPUSH", $key, "" . $value));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function pop($key)
    {
        return false;
    }

    /**
     * @param $key
     * @param bool $remove
     * @return mixed
     */
    public function getFullList($key, $remove = false)
    {

        return false;

    }

    /**
     * @param $key
     * @param array $list
     * @return mixed
     */
    public function pushFullList($key, array $list)
    {

        return false;

    }

    public function info($section = "")
    {
        return false;
    }

    public function keys()
    {
        return [];

    }
}