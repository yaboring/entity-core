<?php

require __DIR__ . '/vendor/autoload.php';

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;

use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\KeyValue\Factory;


$worker = Worker::create();
$factory = new Psr17Factory();
$psr7 = new PSR7Worker(Worker::create(), $factory, $factory, $factory);

$rpc = RPC::create('tcp://127.0.0.1:6001');
$factory = new Factory($rpc);
$localStorage = $factory->select('local');

$dBHost = 'database-service.yaboring-static.svc.cluster.local';
$dBUsername = 'root';
$dBPassword = 'password';
$yaboringDBName = 'yaboring';

$db = new mysqli($dBHost, $dBUsername, $dBPassword, $yaboringDBName);

$db->fetch = function($query) use ($db) {
    $result = $db->query($query);
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $data;
};

$entityID = getenv('YABORING_ENTITY_ID') ?: 'UNKNOWN';

$entityData = ($db->fetch)("
    SELECT e.id, et.title, et.description
    FROM entities e
    JOIN entity_types et ON et.id = e.`type`
    WHERE e.id = {$entityID}
")[0];

$entityData['heartbeatCount'] = 0;

$localStorage->set('entityData', json_encode($entityData));

unset($entityData);

while (true) {

    try {
        $request = $psr7->waitRequest();
        if ($request === null) {
            break;
        }
    } catch (\Throwable $e) {
        $psr7->respond(new Response(400));
        continue;
    }

    try {
        
        $entityData = json_decode($localStorage->get('entityData'), true);

        $entityID = $entityData['id'];
        $entityTypeTitle = $entityData['title'];
        $entityTypeDescription = $entityData['description'];
        $heartbeatCount = $entityData['heartbeatCount'];

        $res = "I am entity #{$entityID}. I am a {$entityTypeTitle}, and my job is {$entityTypeDescription}. Heartbeat count: $heartbeatCount";

        $psr7->respond(new Response(200, [], $res));
        
    } catch (\Throwable $e) {
        $psr7->respond(new Response(500, [], 'Something Went Wrong!'));
        // $psr7->getWorker()->error((string)$e);
    }
    
}
