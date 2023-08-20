<?php

/**
 * 测试队列用的方法,插入队列
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kk1987n/LineQue.git
 * @version 1.0.0
 */
define('LineQue', __DIR__);
define('APP', __DIR__ . '/App');

require_once __DIR__ . '/Lib/Autoload.php';
if (!class_exists('Yjtec\Linque\Lib\Autoload', false)) {
    die('自动加载类错误' . PHP_EOL);
}
\Yjtec\Linque\Lib\Autoload::start();
$DbInstance = new \Yjtec\Linque\Lib\dbJobInstance();
$jobid = $DbInstance->addJob('default', '\App\UserApp', array('img' => time()));
print_r($jobid . PHP_EOL);
