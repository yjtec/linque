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
define('LOGPATH', LineQue . '/LineQue.log');

require_once './Autoload.php';
spl_autoload_register('\Yjtec\Linque\Autoload::autoload');

$DbInstance = new \Yjtec\Linque\Lib\dbJobInstance();
$jobid = $DbInstance->addJob('default', '\Yjtec\Linque\App\UserApp', array('img' => time()));
print_r($jobid . PHP_EOL);
