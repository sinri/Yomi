<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 21:05
 */

/*
 * [SOCKET CONNECTION CONFIG]
 * You need to determine the socket type to use for `socket_type` among 'tcp_ip' or 'unix_socket',
 * for 'tcp_ip', you should give `daemon_port`;
 * and for 'unix_socket', you should give `socket_file`.
 */

$yomi_config['socket_type'] = 'unix_socket';
$yomi_config['daemon_port'] = '9999';
$yomi_config['socket_file'] = '/tmp/yomiSocket';


/*
 * [MULTI-WORKER CONFIG]
 * You can set the count of worker processes as `worker_count`.
 * If you want to get all workers or none, you can set `worker_must_fulfilled` as true,
 * that would cause an exception and stop when any one worker process cannot start.
 */

$yomi_config['worker_count'] = 2;
$yomi_config['worker_must_fulfilled'] = true;

/*
 * [OVERRIDE CONFIG]
 * To override for worker process, you may set your own class name (with full namespace) in `override_class`.
 * Leave it as null if you do not override.
 */

$yomi_config['override_class'] = null;