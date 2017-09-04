<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/9/2
 * Time: 20:58
 */

require_once __DIR__ . '/src/helper/YomiHelper.php';

spl_autoload_register(function ($class_name) {
    $file_path = \sinri\yomi\helper\YomiHelper::getFilePathOfClassNameWithPSR0(
        $class_name,
        'sinri\yomi',
        __DIR__ . '/src',
        '.php'
    );
//    echo $file_path." FOUND".PHP_EOL;
    if ($file_path) {
        require_once $file_path;
    }
});