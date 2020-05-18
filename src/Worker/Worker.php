<?php

namespace Yjtec\Linque\Worker;

use Exception;
use Yjtec\Linque\Config\Conf;
use Yjtec\Linque\Lib\dbJobInstance;
use Yjtec\Linque\Lib\ProcLine;
use const LOGPATH;

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
class Worker {

    private $Que; //队列名
    private $interval; //循环时间间隔
    private $DbInstance = null; //数据库操作实例
    private $procLine = null; //日志记录
    private $system;

    public function __construct($Que, $interval) {
        $this->Que = $Que;
        $this->interval = $interval;
        $this->DbInstance = new dbJobInstance();
        $this->procLine = new ProcLine(LOGPATH);
        $this->system = Conf::getSystemPlatform();
    }

    public function startWork() {
        //此处已经是子进程的子进程了,可以在此处进行下一步逻辑了
        $this->procLine->EchoAndLog('子进程开始循环PID=' . $this->getMyPid() . PHP_EOL);
        while (1) {
			if($this->isParentDead()){
				return true;
			}
//            pcntl_signal_dispatch(); //查看信号队列
            if ($job = $this->getAJob()) {
                $this->procLine->EchoAndLog('子进程即将开始一个新Job,PID=' . $this->getMyPid() . '，JobInfo:' . json_encode($job) . PHP_EOL);
                try {
                    $this->run($job); //执行
                } catch (Exception $ex) {
                    $this->procLine->EchoAndLog('新Job执行发生异常PID=' . $this->getMyPid() . ':' . json_encode($ex) . PHP_EOL);
                }
                $this->procLine->EchoAndLog('新Job执行结束PID=' . $this->getMyPid() . ',JobId=' . $job['id'] . PHP_EOL);
            }

            usleep($this->interval * 1000000); //休眠多少秒
        }
    }

    /**
     * 获取job
     * @param type $job
     * @return boolean
     */
    private function getAJob() {
        $job = $this->DbInstance->popJob($this->Que);
        if (!$job) {
            return false;
        }
        return $job;
    }

    /**
     * job执行状态,正常的job是从执行这个方法
     * 这个方法主动去实例化用户指定的类,并主动调用其run方法
     * @param type $job
     * @return boolean
     */
    public function run($job) {
        $this->procLine->EchoAndLog('用户APP开始执行:' . $job['id'] . PHP_EOL);
        $this->DbInstance->workingOn($job); //开始执行
        if ($this->system == 'linux') {
            $rs = $this->forkProc($job);
        } else {
            $rs = $this->appStart($job);
        }
        if ($rs) {
            $this->procLine->EchoAndLog('用户APP执行成功:' . $job['id'] . PHP_EOL);
            $this->DbInstance->workingDone($job); //执行完成
            return true;
        }
        $this->DbInstance->workingFail($job); //执行失败
        $this->procLine->EchoAndLog('用户App执行失败:' . $job['id'] . PHP_EOL);
        return false;
    }

    /**
     * 将app的run方法放进独立的进程执行,两个好处
     * 1,保护instance进程
     * 2,run方法出错退出意味着job执行失败
     * @param type $job
     * @return boolean
     */
    public function forkProc($job) {
        $pid = pcntl_fork();
        if ($pid > 0) {//原进程，拿到子进程的pid
            $status = null;
            $exitPid = pcntl_wait($status); //等待子进程的信号
            if ($exitPid && $status == 0) {
                return true;
            }
        } elseif ($pid == 0) {
            $this->appStart($job);
            exit(0); //这里必须退出子进程，这个0对应上边的pantl_wait的status
        }
        return false;
    }

    public function appStart($job) {
        $instance = $this->getAppInstance($job);
        if ($instance && is_callable(array($instance, 'before'))) {
            $instance->before(); //执行用户的before方法
        }
        if ($instance && is_callable(array($instance, 'run'))) {
            $instance->run(); //执行用户的run方法
        }
        if ($instance && is_callable(array($instance, 'after'))) {
            $instance->after(); //执行用户的after方法
        }
        unset($instance); //用完销毁
        return true;
    }

    /**
     * 获取用户指定的类,并初始化其参数
     * @param type $job
     * @return type
     * @throws Exception
     * @throws Exception
     */
    public function getAppInstance($job) {
        if (!class_exists($job['class'])) {
            $this->procLine->EchoAndLog('找不到用户APP:' . $job['class'] . PHP_EOL);
            return false;
        }
        if (!method_exists($job['class'], 'run')) {
            $this->procLine->EchoAndLog('用户APP找不到run方法:' . $job['class'] . PHP_EOL);
            return false;
        }
        return new $job['class']($job); //实例化job
    }

    public function getMyPid() {
        return $this->system == 'linux' ? posix_getpid() : getmypid();
    }

	public function isParentDead(){
		if($this->system == 'linux'&&is_callable("exec")&&$this->masterPid){
			exec("ps p ".$this->masterPid."|awk '{if(NR>1)print}'",$str,$re);
			if($re==0){
				if(!$str){
					$this->procLine->EchoAndLog('未检测到父进程，子进程将退出:' . $this->getMyPid() . PHP_EOL);
					return true;
				}
				$arg=explode(" ",trim(preg_replace("/\s(?=\s)/","\\1", $str[0])));
				unset($arg[0],$arg[1],$arg[2],$arg[3],$arg[4]);
				$arg=array_values($arg);
				if($arg==$_SERVER['argv']){
					return false;
				}
				$this->procLine->EchoAndLog('检测到一个奇怪的父进程，子进程将退出:' . $this->getMyPid()."，进程参数：".json_encode($str) . PHP_EOL);
				return true;
			}
		}
		return false;
	}
}
