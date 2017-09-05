<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:57
 */

require_once __DIR__ . '/../../autoload.php';

$socketAgent = new \sinri\yomi\socket\SocketAgent();
$socketAgent->configSocketAsTcpIp("127.0.0.1", '11111');

$socketAgent->runServer(function ($client) {
    $content = stream_get_contents($client);
    $pairName = stream_socket_get_name($client, true);

    echo "Customized Received from [{$pairName}]: " . $content . PHP_EOL;

    fwrite($client, "Data received!");
    return \sinri\yomi\socket\SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT;
});