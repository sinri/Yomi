<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 20:57
 */

date_default_timezone_set("Asia/Shanghai");

require_once __DIR__ . '/../autoload.php';

$yomi_config = [];
require_once __DIR__ . '/yomi.conf.php';

// argv
// php yomi.php [start|stop|force-stop|status]

if ($argc < 2) {
    echo 'Plz run as `php yomi.php [start|stop|force-stop|status]` !' . PHP_EOL;
    exit(-1);
}

$hades = new \sinri\yomi\hades\Hades($yomi_config);

switch ($argv[1]) {
    case 'start':
        $hades->start();
        break;
    case 'stop':
        $hades->sendStopCommand();
        break;
    case 'force-stop':
        $hades->sendForceStopCommand();
        break;
    case 'status':
        $hades->sendStatusCommand();
        break;
}

