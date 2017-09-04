<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:01
 */

namespace sinri\yomi\single;


use sinri\yomi\entity\YomiRequest;
use sinri\yomi\helper\YomiHelper;
use sinri\yomi\socket\SocketAgent;

class SingleJSONServer
{
    /**
     * @var SocketAgent
     */
    protected $socketAgent;

    public function __construct($socketAgent)
    {
        $this->socketAgent = $socketAgent;
    }

    public function listen()
    {
        $this->socketAgent->runServer(function ($client) {
            $pairName = stream_socket_get_name($client, true);
            YomiHelper::log("INFO", 'Accepted from ' . $pairName);

            stream_set_timeout($client, 0, 100000);

            //$content = stream_get_contents($client);

            $content = '';
            while (!feof($client)) {
//                YomiHelper::log('DEBUG', 'Loop read [{$pairName}] once:');
//                $meta = stream_get_meta_data($client);
//                YomiHelper::log('DEBUG', 'MetaData from [{$pairName}]: ' . json_encode($meta));

                $got = fread($client, 1024);
                $content .= $got;

                $json = json_decode($content, true);
                if (is_array($json)) {
                    // over
                    break;
                }
            }

            YomiHelper::log("DEBUG", "Yomi received data: " . PHP_EOL . $content . PHP_EOL);

            //$pairName = stream_socket_get_name($client, true);

            //$this->log("INFO", "YomiSingle Received from [{$pairName}]: " . $content);

            $contentParsed = json_decode($content, true);
            if (!is_array($contentParsed)) {
                YomiHelper::log("ERROR", "YomiSingle [{$pairName}] Cannot parse as JSON!");
                fwrite($client, json_encode(['code' => '400', 'data' => 'NOT JSON:' . PHP_EOL . $contentParsed]));
                return SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT;
            }

            if (!isset($contentParsed['type']) || !isset($contentParsed['data'])) {
                YomiHelper::log("ERROR", "YomiSingle [{$pairName}] Not a correct input!");
                fwrite($client, json_encode(['code' => '400', 'data' => 'NOT CORRECT INPUT']));
                return SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT;
            }

            $type = $contentParsed['type'];
            $data = $contentParsed['data'];

            $request = new YomiRequest($type, $data);

            $code = $this->handleRequest($request, $responseBody);

//            $meta=stream_get_meta_data($client);
//            YomiHelper::log('DEBUG','Handled [{$pairName}], code is '.$code.' and  meta: '.json_encode($meta));

            if ($code == '300') {
                YomiHelper::log("INFO", "For [{$pairName}] has forked a client [{$responseBody}] to handle, parent leaves.");
                return SocketAgent::SERVER_CALLBACK_COMMAND_NONE;
            } elseif ($code == '200') {
                fwrite($client, json_encode(['code' => $code, 'data' => $responseBody]));
                fflush($client);
                $closed = fclose($client);
                YomiHelper::log("DEBUG", "Try to close client [{$pairName}] and die... closed? " . json_encode($closed));
                exit(0);
            } else {
                //exception, often 500
                fwrite($client, json_encode(['code' => $code, 'data' => $responseBody]));
            }
            return SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT;
        });
    }

    /**
     * @param YomiRequest $request
     * @param string $body
     * @return int
     * @throws \Exception
     */
    protected function handleRequest($request, &$body = '')
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            //failed
            $body = "CANNOT START HANDLE PROCESS!";
            return '500';
        } elseif ($pid) {
            //as parent
            YomiHelper::log("INFO", "YomiSingle Created child process [{$pid}]!");
            $body = $pid;
            return '300';
        } else {
            //as child
            $child_pid = getmypid();
            try {
                $request->handle(function ($data) use ($child_pid) {
                    YomiHelper::log("INFO", "child [{$child_pid}] handle data: " . json_encode($data));
                    if ($data == 'error') {
                        throw new \Exception("data is error");
                    }
                });
                return '200';
            } catch (\Exception $exception) {
                $body = $exception->getMessage();
                return '500';
            }
        }
    }


}