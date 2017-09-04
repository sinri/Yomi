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
    const SERVER_CALLBACK_COMMAND_NONE = "NONE";
    const SERVER_CALLBACK_COMMAND_CLOSE_CLIENT = "CLOSE_CLIENT";
    const SERVER_CALLBACK_COMMAND_CLOSE_SERVER = "CLOSE_SERVER";

    protected $address;
    protected $port;
    protected $listenTimeout;
    protected $peerName;

    /**
     * SocketAgent constructor.
     * @param string $address
     * @param int $port
     */
    public function __construct($address, $port)
    {
        $this->address = $address;
        $this->port = $port;
        $this->listenTimeout = -1;
        $this->peerName = __CLASS__;
    }

    /**
     * @param callable|null $requestHandler
     * @param callable|null $bindStatusHandler
     */
    public function runServer($requestHandler = null, $bindStatusHandler = null)
    {
        $server = stream_socket_server("tcp://{$this->address}:{$this->port}", $errorNumber, $errorMessage);

        if ($bindStatusHandler) {
            $bind_ok = ($server === false ? false : true);
            call_user_func_array($bindStatusHandler, [$bind_ok]);
        }

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
                $callback_command = self::SERVER_CALLBACK_COMMAND_NONE;
                $pairName = stream_socket_get_name($client, true);
                if ($requestHandler) {
                    $callback_command = call_user_func_array($requestHandler, [$client]);
                } else {
                    //just a demo
                    $content = stream_get_contents($client);
                    YomiHelper::log("INFO", "Received from [{$pairName}]: " . $content);
                }
                if (
                    $callback_command == self::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT
                    || $callback_command == self::SERVER_CALLBACK_COMMAND_CLOSE_SERVER
                ) {
                    fclose($client);
                    YomiHelper::log("INFO", "CLOSE CLIENT [{$pairName}]");
                }
                if ($callback_command == self::SERVER_CALLBACK_COMMAND_CLOSE_SERVER) {
                    YomiHelper::log("INFO", "CLOSE SERVER as required");
                    break;
                }
            }
        }
    }

    /**
     * @param callable|null $callback
     */
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