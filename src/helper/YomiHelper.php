<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/4
 * Time: 10:16
 */

namespace sinri\yomi\helper;


class YomiHelper
{
    /**
     * 按照PSR-0规范，不过PSR-4看起来也是支持的
     * @since 2.0.0 turn to static
     * @param string $class_name such as sinri\enoch\test\routing\controller\SampleHandler
     * @param string $base_namespace such as sinri\enoch
     * @param string $base_path /code/sinri/enoch
     * @param string $extension
     * @return null|string
     */
    public static function getFilePathOfClassNameWithPSR0($class_name, $base_namespace, $base_path, $extension = '.php')
    {
        if (strpos($class_name, $base_namespace) === 0) {
            $class_file = str_replace($base_namespace, $base_path, $class_name);
            $class_file .= $extension;
            $class_file = str_replace('\\', '/', $class_file);
            return $class_file;
        }
        return null;
    }

    /**
     * @param string $level DEBUG INFO WARNING ERROR
     * @param string $message
     */
    public static function log($level, $message)
    {
        $pid = getmypid();
        echo "[" . date("Y-m-d H:i:s") . "|" . microtime(true) . "] <{$pid}:{$level}> " . $message . PHP_EOL;
    }

    /**
     * @param array $signals
     * @param callable $callback
     */
    public static function defineSignalHandler($signals, $callback)
    {
        declare(ticks=1);
        foreach ($signals as $signal) {
            pcntl_signal($signal, $callback);
        }
    }
}