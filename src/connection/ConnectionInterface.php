<?php

namespace Redis\Connection;


interface ConnectionInterface {
    public function connect($port);
    public function getInstanceBySlot($slot, $startingPort, $masterInstances);
    public function read($key);
    public function write($key, $value, $cacheTime = false);
    public function hmRead($key, array $fields = array());
    public function hmWrite($key, array $fields = array(), array $values = array());
    public function hmRemove($key, array $fields = array());
    public function hScan($key, $match);
    public function getSlot($key);
    public function multiCmd(array $cmd = array());
    public function push($key, $value);
    public function pop($key);
    public function getFullList($key, $remove = false);
    public function pushFullList($key, array $list);
    public function info($section);
}