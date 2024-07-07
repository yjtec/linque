<?php

namespace Yjtec\Linque\Worker;

use Exception;
use Yjtec\Linque\Config\Conf;
use Yjtec\Linque\Lib\dbJobInstance;
use Yjtec\Linque\Lib\ProcLine;

/**
 * 执行job的子进程
 * 本进程主要获取job,更新job等操作
 * 实际最终执行用户app的,也就是执行run方法的,为本进程开启的子进程
 * 这样做可以保护本进程,也可以获取run方法有没有意外终止导致执行失败
 * 
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kk1987n/LineQue.git
 * @version 1.0.0
 */
class Monitor
{

    private $app;
    private $appInstance;
    private $interval; //循环时间间隔
    private $procLine = null; //日志记录
    private $system;
    public $masterPid;
    private $logPath = "";

    public function __construct($app, $interval, $logPath = "")
    {
        $this->logPath = $logPath ? $logPath : dirname(__FILE__) . '/../monitor.log';
        $this->interval = $interval;
        $this->procLine = new ProcLine($this->logPath);
        $this->system = Conf::getSystemPlatform();
        $this->app = $app;
    }

    public function startWork()
    {
        //此处已经是子进程的子进程了,可以在此处进行下一步逻辑了
        $this->procLine->EchoAndLog('监控进程开始守护，PID=' . $this->getMyPid() . PHP_EOL);
        $title = cli_get_process_title();
        while (1) {
            if ($this->isParentDead()) {
                return true;
            }
            try {
                cli_set_process_title($title . ' doing');
                $this->appStart(); //执行
                cli_set_process_title($title);
            } catch (Exception $ex) {
                $this->procLine->EchoAndLog('队列主进程(' . $this->getMyPid() . ')执行Job发生异常:' . json_encode($ex) . PHP_EOL);
            }

            usleep($this->interval * 1000000); //休眠多少秒
        }
    }

    public function appStart()
    {
        $instance = $this->getAppInstance();
        if ($instance && is_callable(array($instance, 'before'))) {
            $instance->before(); //执行用户的before方法
        }
        if ($instance && is_callable(array($instance, 'run'))) {
            $instance->run(); //执行用户的run方法
        }
        if ($instance && is_callable(array($instance, 'after'))) {
            $instance->after(); //执行用户的after方法
        }
        return true;
    }

    /**
     * 获取用户指定的类,并初始化其参数
     * @param type $job
     * @return type
     * @throws Exception
     * @throws Exception
     */
    public function getAppInstance()
    {
        if ($this->appInstance) {
            return $this->appInstance;
        }
        if (!class_exists($this->app)) {
            $this->procLine->EchoAndLog('找不到用户APP:' . $this->app . PHP_EOL);
            return false;
        }
        if (!method_exists($this->app, 'run')) {
            $this->procLine->EchoAndLog('用户APP找不到run方法:' . $this->app . PHP_EOL);
            return false;
        }
        $this->appInstance = new $this->app();
        $this->procLine->EchoAndLog('用户APP实例化成功：' . $this->app . PHP_EOL);
        return $this->appInstance; //实例化job
    }

    public function getMyPid()
    {
        return $this->system == 'linux' ? posix_getpid() : getmypid();
    }

    public function isParentDead()
    {
        if ($this->system == 'linux' && is_callable("exec") && $this->masterPid) {
            $cmd = "ps -ef| grep " . $this->getMyPid() . "|grep -v grep|awk '{print$3}'";
            exec($cmd, $str, $re);
            if ($re != 0 || !$str || !isset($str[0]) || $this->masterPid != intval($str[0])) {
                $this->procLine->EchoAndLog('未检测到父进程，父进程ID：' . $this->masterPid . '，子进程将退出：' . $this->getMyPid() . "，命令：" . $cmd . "，进程参数：" . json_encode($str) . PHP_EOL);
                return true;
            }
        }
        return false;
    }
}
