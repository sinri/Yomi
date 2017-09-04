<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 23:15
 */

//require_once __DIR__ . '/../../src/socket/SocketAgent.php';
require_once __DIR__ . '/../../autoload.php';

$socketAgent = new \sinri\yomi\socket\SocketAgent("127.0.0.1", '11111');

$content = "ping";

$socketAgent->runClient(function ($client) use ($content) {
    fwrite($client, $content);
    $response = '';
    while (!feof($client)) {
        $response .= fgets($client, 1024);
    }
    echo "GET RESPONSE: " . $response . PHP_EOL;
});

echo "Client OVER" . PHP_EOL;