<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:33
 */

namespace sinri\yomi\socket;


use sinri\yomi\helper\YomiHelper;

class SocketAgent
{
    protected $address;
    protected $port;
    protected $listenTimeout;
    protected $peerName;

    public function __construct($address, $port)
    {
        $this->address = $address;
        $this->port = $port;
        $this->listenTimeout = -1;
        $this->peerName = __CLASS__;
    }

    public function runServer($callback = null)
    {
        $server = stream_socket_server("tcp://{$this->address}:{$this->port}", $errorNumber, $errorMessage);

        if ($server === false) {
            throw new \UnexpectedValueException("Could not bind to socket: $errorMessage");
        }

        YomiHelper::defineSignalHandler([SIGINT, SIGTERM, SIGHUP], function ($signal_number) {
            YomiHelper::log("ERROR", "SIGNAL: " . $signal_number);
            exit();
        });
        YomiHelper::defineSignalHandler([SIGUSR1], function ($signal_number) {
            YomiHelper::log("INFO", "USER SIGNAL: " . $signal_number);
        });

        YomiHelper::log("INFO", "BEGIN LISTEN...");

        while (true) {
            $client = stream_socket_accept($server, $this->listenTimeout, $this->peerName);

            if ($client) {
                $shouldCloseClient = true;
                $pairName = stream_socket_get_name($client, true);
                if ($callback) {
                    $shouldCloseClient = call_user_func_array($callback, [$client]);
                } else {
                    //just a demo
                    $content = stream_get_contents($client);
                    YomiHelper::log("INFO", "Received from [{$pairName}]: " . $content);
                }
                if ($shouldCloseClient) {
                    fclose($client);
                    YomiHelper::log("INFO", "CLOSE CLIENT [{$pairName}]");
                }
            }
        }

    }

    public function runClient($callback = null)
    {
        $client = stream_socket_client("tcp://{$this->address}:{$this->port}", $errNumber, $errorMessage, $this->listenTimeout);

        if ($client === false) {
            throw new \UnexpectedValueException("Failed to connect: $errorMessage");
        }
        if ($callback) {
            call_user_func_array($callback, [$client]);
        } else {
            fwrite($client, 'PING');
            $response = '';
            while (!feof($client)) {
                $response .= fgets($client, 1024);
            }

            //$result=stream_socket_sendto($client, $content);
            //$response = stream_get_contents($client);

            YomiHelper::log("DEBUG", " sent PING, response: " . $response);
        }
        fclose($client);
    }
}