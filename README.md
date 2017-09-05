# Yomi
A queue handler for PHP (as it should be...)

Now it is a mix...

## SocketAgent

SocketAgent is a framework for socket connection.

```php
// create instance
$socketAgent = new \sinri\yomi\socket\SocketAgent("127.0.0.1", '11111');
```

### Server Mode

```php
// define request handler
$requestHandler = function($client){
    // read from request stream
    $content = stream_get_contents($client);
    // write back to stream
    fwrite($client, "Data received!");
    // finish and report finish style among the consts of SocketAgent:
    // SERVER_CALLBACK_COMMAND_CLOSE_CLIENT / SERVER_CALLBACK_COMMAND_CLOSE_SERVER / SERVER_CALLBACK_COMMAND_NONE
    return \sinri\yomi\socket\SocketAgent::SERVER_CALLBACK_COMMAND_CLOSE_CLIENT;
}
$bindStatusHandler=function($bindOK){
    // $bindOK is a boolean, to notify if bind is ok before any possible exception thrown.
}
// run as server, would continue till SERVER_CALLBACK_COMMAND_CLOSE_SERVER returned by $requestHandler.
$socketAgent->runServer($requestHandler,$bindStatusHandler);
```

### Client Mode

```php
// define request sender
$callback=function($connection){
    // write to stream
    fwrite($client, $content);
    // read response from stream till FEOF
    $response = '';
    while (!feof($client)) {
        $response .= fgets($client, 1024);
    }
    // print the reponse
    echo "GET RESPONSE: " . $response . PHP_EOL;
}
// run as client once
$socketAgent->runClient($callback);
```

### A demo: SingleJSONServer

SingleJSONServer is a server/client package. 

When runs as server, it listens to ip/port and fork a child process to handle the coming request. 
The request and response are both in JSON format. 

When runs as client, it can send a request to the open server.

The multi processes are not limited, so it is only a demo for SocketAgent Application.

## Yomi based on Hades

You need a config file `yomi.conf.php`;

Override `Hades::workForChild` as after story.

Run and see effects. 

```bash
php yomi.php [start|stop|force-stop|status]
```