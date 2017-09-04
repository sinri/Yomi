<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/3
 * Time: 22:41
 */

require_once __DIR__ . '/../../autoload.php';

$socketAgent = new \sinri\yomi\socket\SocketAgent('127.0.0.1', '12345');

$socketAgent->runClient(function ($client) {
    $pairName = stream_socket_get_name($client, true);
    \sinri\yomi\helper\YomiHelper::log("INFO", "Client linked to " . $pairName);
    stream_set_timeout($client, 0, 100000);

    $content = json_encode(['type' => 'error', 'data' => 'error']);
    fwrite($client, $content);
    fflush($client);
    $response = '';
    \sinri\yomi\helper\YomiHelper::log("INFO", "Request Sent");
    while (!feof($client)) {
        \sinri\yomi\helper\YomiHelper::log("DEBUG", "Waiting for response...");
        $meta = stream_get_meta_data($client);
        \sinri\yomi\helper\YomiHelper::log('DEBUG', 'read once meta: ' . json_encode($meta));

        $got = fread($client, 1024);
        $response .= $got;
        \sinri\yomi\helper\YomiHelper::log("DEBUG", "read from [{$pairName}] : " . json_encode($got));

        $json = json_decode($response, true);
        if (is_array($json)) {
            // over
            break;
        }
    }

    // this might got none
    //$response=stream_get_contents($client);

    \sinri\yomi\helper\YomiHelper::log("DEBUG", "GET RESPONSE: " . $response);
});

\sinri\yomi\helper\YomiHelper::log("INFO", "OVER");