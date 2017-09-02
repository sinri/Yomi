<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:33
 */

namespace sinri\yomi\socket;


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

        echo "BEGIN LISTEN..." . PHP_EOL;

        while (true) {
            $client = stream_socket_accept($server, $this->listenTimeout, $this->peerName);

            if ($client) {
                if ($callback) {
                    call_user_func_array($callback, [$client]);
                } else {
                    //just a demo
                    $content = stream_get_contents($client);
                    $pairName = stream_socket_get_name($client, true);

                    echo "Received from [{$pairName}]: " . $content . PHP_EOL;
                }
                fclose($client);
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

            echo __METHOD__ . " sent PING, response: " . $response . PHP_EOL;
        }
        fclose($client);


        //return $response;
    }
}