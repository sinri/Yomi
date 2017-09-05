<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/4
 * Time: 15:29
 */

namespace sinri\yomi\hades;


use sinri\yomi\helper\YomiHelper;
use sinri\yomi\socket\SocketAgent;

class Hades
{
    const WORKER_INIT = "INIT";
    const WORKER_NORMAL = "NORMAL";
    const WORKER_STOP = "STOP";

    protected $config;
    protected $childPidPool;
    protected $workProcessSwitch;

    /**
     * Hades constructor.
     * Config is an array with keys:
     * > 'socket_type' string, tcp_ip|unix_socket
     * > 'socket_file' string, socket file path
     * > 'daemon_port' integer,
     * > 'worker_count'  integer,
     * > 'worker_must_fulfilled' boolean.
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->childPidPool = [];
        $this->workProcessSwitch = self::WORKER_INIT;
    }

    /**
     * @param array $config
     * @return Hades
     */
    public static function yomiFactory($config)
    {
        $classname = $config['override_class'];
        if ($classname) {
            return new $classname($config);
        } else {
            return new Hades($config);
        }
    }

    /**
     * @return SocketAgent
     */
    protected function createSocketAgent()
    {
        $socketAgent = new SocketAgent();
        if ($this->config['socket_type'] == 'tcp_ip') {
            $socketAgent->configSocketAsTcpIp('127.0.0.1', $this->config['daemon_port']);
        } elseif ($this->config['socket_type'] == 'unix_socket') {
            $socketAgent->configSocketAsUnixDomain($this->config['socket_file']);
        }
        return $socketAgent;
    }

    /**
     * Create worker processes and listen to socket as daemon
     */
    public function start()
    {
        // create child processes as config defines
        for ($i = 0; $i < $this->config['worker_count']; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                //error, still in parent
                YomiHelper::log("ERROR", "CANNOT FORK!");
                // if config defined `worker_must_fulfilled`, stop generated processes and stop.
                if ($this->config['worker_must_fulfilled']) {
                    YomiHelper::log("ERROR", "Config [worker_must_fulfilled] found, try to stop...");
                    $this->stop(null);
                    break;
                }
            } elseif ($pid) {
                //as parent, continue
                YomiHelper::log("INFO", "FORKED " . $pid);
                $this->childPidPool[] = $pid;
            } else {
                //as child, define signal handler and begin working
                YomiHelper::defineSignalHandler([SIGUSR1], function ($signal_number) {
                    YomiHelper::log("INFO", "USER SIGNAL: " . $signal_number);
                    $this->workProcessSwitch = self::WORKER_STOP;
                });
                YomiHelper::defineSignalHandler([SIGINT, SIGTERM, SIGHUP], function ($signal_number) {
                    YomiHelper::log("INFO", "SYSTEM SIGNAL: " . $signal_number);
                    $this->workProcessSwitch = self::WORKER_STOP;
                    exit();
                });
                YomiHelper::defineSignalHandler([SIGUSR2], function ($signal_number) {
                    YomiHelper::log("INFO", "SYSTEM SIGNAL: " . $signal_number);
                    $this->workProcessSwitch = self::WORKER_NORMAL;
                });
                $this->workForChild();
                return;
            }
        }
        // as parent, build up monitor
        try {
            // if parent received STOP-SIGNAL, go force stop.
            YomiHelper::defineSignalHandler([SIGINT, SIGTERM, SIGHUP], function ($signal_number) {
                YomiHelper::log("ERROR", "SIGNAL: " . $signal_number . " Would there be any eggs not broken under the reversed nest? FORCE STOP ALL!");
                $this->forceStop(null);
                exit();
            });
            // try to bind port
            $socketAgent = $this->createSocketAgent();
            $socketAgent->runServer(function ($client) {
                $pairName = stream_socket_get_name($client, true);
                YomiHelper::log("INFO", 'Accepted from ' . $pairName);

                stream_set_timeout($client, 0, 100000);

                $content = '';
                $json = [];
                while (!feof($client)) {
                    $got = fread($client, 1024);
                    $content .= $got;

                    $json = json_decode($content, true);
                    if (is_array($json)) {
                        break;
                    }
                }

                switch ($json['order']) {
                    case 'stop':
                        $this->stop($client);
                        fwrite($client, "OVER");
                        return SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_SERVER;
                        break;
                    case 'force-stop':
                        $this->forceStop($client);
                        fwrite($client, "OVER");
                        return SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_SERVER;
                        break;
                    case 'status':
                        $this->status($client);
                        fwrite($client, "OVER");
                        return SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT;
                        break;
                    default:
                        YomiHelper::log("WARNING", "Unknown order came, hacked?");
                }
                fwrite($client, "OVER");
                return SocketAgent::SERVER_CALLBACK_COMMAND_NONE;
            }, function ($bindOK) {
                if ($bindOK) {
                    foreach ($this->childPidPool as $pid) {
                        posix_kill($pid, SIGUSR2);
                    }
                }
            }, function ($serverSocket, $signal_number) {
                YomiHelper::log("ERROR", "SIGNAL: " . $signal_number . " Would there be any eggs not broken under the reversed nest? FORCE STOP ALL!");
                $this->forceStop(null);
                if ($serverSocket) {
                    YomiHelper::log("INFO", "Let SocketAgent to terminate the server socket...");
                }
            });
        } catch (\Exception $exception) {
            // cannot bind port, might an instance there already
            YomiHelper::log("ERROR", "Socket Agent Error! Cannot run as daemon, try to stop...");
            $this->stop(null);
        }
    }

    /**
     * @param resource $client
     */
    protected function stop($client = null)
    {
        // Notify all worker processes as SIGUSR1 to stop working.
        foreach ($this->childPidPool as $pid) {
            $done = posix_kill($pid, SIGUSR1);
            YomiHelper::log("INFO", "Sent SIGUSR1 to child [{$pid}]... " . json_encode($done));
        }
        // Wait for all worker processes exit
        foreach ($this->childPidPool as $pid) {
            YomiHelper::log("INFO", "Waiting for child [{$pid}] to stop...");
            $result = pcntl_waitpid($pid, $status);
            YomiHelper::log("INFO", "Child [{$pid}] stopped, result: " . json_encode($result) . " status: " . json_encode($status));
            if ($client) {
                fwrite($client, "PID [{$pid}] STOPPED." . PHP_EOL);
            }
        }

        YomiHelper::log("INFO", "Behold, I will send you Elijah the prophet before the coming of the great and dreadful day of the LORD. (Malachi 5:5)");
    }

    /**
     * this might be overrode to realize the customized request
     */
    protected function workForChild()
    {
        while ($this->workProcessSwitch == self::WORKER_INIT) {
            time_nanosleep(0, 100000000);
            YomiHelper::log("DEBUG", "current worker status: " . $this->workProcessSwitch);
        }
        while ($this->workProcessSwitch != self::WORKER_STOP) {
            // You might only rewrite this part when override.
            YomiHelper::log("DEBUG", "I am working...");
            sleep(rand(2, 6));
        }
    }

    /**
     * @param null|resource $client
     */
    protected function forceStop($client = null)
    {
        // Force all worker processes to exit with SIGINT.
        foreach ($this->childPidPool as $pid) {
            $done = posix_kill($pid, SIGINT);
            YomiHelper::log("INFO", "Sent SIGINT to child [{$pid}]... " . json_encode($done));
        }
        // Wait for all worker processes exit
        foreach ($this->childPidPool as $pid) {
            YomiHelper::log("INFO", "Waiting for child [{$pid}] to stop...");
            $result = pcntl_waitpid($pid, $status);
            YomiHelper::log("INFO", "Child [{$pid}] stopped, result: " . json_encode($result) . " status: " . json_encode($status));
            if ($client) {
                fwrite($client, "PID [{$pid}] STOPPED." . PHP_EOL);
            }
        }
        YomiHelper::log("INFO", "And I saw a new heaven and a new earth: for the first heaven and the first earth were passed away; and there was no more sea. (Revelation 21:1)");
    }

    /**
     * @param resource $client
     */
    protected function status($client)
    {
        if ($client) {
            fwrite($client, "Totally " . count($this->childPidPool) . " child processes generated." . PHP_EOL);
            foreach ($this->childPidPool as $pid) {
                $pgid = posix_getpgid($pid);
                if ($pgid === false) {
                    fwrite($client, "PID [{$pid}] NOT RUNNING..." . PHP_EOL);
                } else {
                    fwrite($client, "PID [{$pid}] RUNNING (generated by {$pgid})..." . PHP_EOL);
                }
            }
        }
    }

    /**
     * @param string $order
     */
    protected function sendOrder($order)
    {
        try {
            $socketAgent = $this->createSocketAgent();
            $socketAgent->runClient(function ($client) use ($order) {
                fwrite($client, json_encode(['order' => $order]));

                $response = '';
                while (!feof($client)) {
                    $got = fgets($client, 1024);
                    if ($got == 'OVER') {
                        break;
                    }
                    $response .= $got;
                }

                YomiHelper::log("INFO", "RESPONSE:" . PHP_EOL . $response);
            });
        } catch (\Exception $exception) {
            YomiHelper::log("ERROR", "Not running? Exception: " . $exception->getMessage());
        }
    }

    /**
     *
     */
    public function sendStopCommand()
    {
        $this->sendOrder('stop');
    }

    /**
     *
     */
    public function sendForceStopCommand()
    {
        $this->sendOrder('force-stop');
    }

    /**
     *
     */
    public function sendStatusCommand()
    {
        $this->sendOrder('status');
    }
}