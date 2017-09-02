<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:57
 */

require_once __DIR__ . '/../../src/socket/SocketAgent.php';

$socketAgent = new \sinri\yomi\socket\SocketAgent("127.0.0.1", '11111');

$socketAgent->runServer(function ($client) {
    $content = stream_get_contents($client);
    $pairName = stream_socket_get_name($client, true);

    echo "Customized Received from [{$pairName}]: " . $content . PHP_EOL;

    fwrite($client, "Data received!");
    //stream_socket_sendto($client,"Data Received!");
});