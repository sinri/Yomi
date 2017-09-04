<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 22:02
 */

require_once __DIR__ . '/../../autoload.php';

$socketAgent = new \sinri\yomi\socket\SocketAgent('127.0.0.1', '12345');
$single = new \sinri\yomi\single\SingleJSONServer($socketAgent);

$single->listen();