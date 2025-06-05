<?php

require __DIR__ . '/vendor/autoload.php';

use Spiral\RoadRunner\Environment;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\KeyValue\Factory;

$rpc = RPC::create('tcp://127.0.0.1:6001');
$factory = new Factory($rpc);
$localStorage = $factory->select('local');

while (true) {

    echo "[Tick] Doing some stuff...";

    $entityData = json_decode($localStorage->get('entityData'), true);
    $entityData['heartbeatCount'] += 1;
    $localStorage->set('entityData', json_encode($entityData));

    sleep(5);
}