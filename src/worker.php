#!/usr/bin/env php
<?php
/**
 * 主入口
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kk1987n/LineQue.git
 * @version 1.0.0
 */
define('LineQue', __DIR__); //LineQue目录绝对路径,autoload中用到了,要加载类
// 只允许在cli下面运行  
if (php_sapi_name() != "cli") {
    die("仅支持命令行模式\n");
}
if (!class_exists('\\Redis', false)) {
    die('必须开启Redis扩展' . PHP_EOL);
}
$QUE = getenv('QUE'); //队列名
$INTERVAL = getenv('INTV'); //worker循环间隔
$queue = $QUE ? $QUE : 'defaults'; //默认队列名

require_once './Autoload.php';
spl_autoload_register('\Yjtec\Linque\Autoload::autoload');

$worker = new \Yjtec\Linque\Worker\Master($queue, $INTERVAL > 0 ? $INTERVAL : 5, 0, null, 'Yjtec\\Linque\\App\\Monitor'); //主进程
$worker->startWork();
