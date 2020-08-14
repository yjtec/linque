<?php

namespace Yjtec\Linque\Lib;

use Yjtec\Linque\Config\Conf;
use Yjtec\Linque\Lib\Redis\RedisDb;
use const LOGPATH;

/**
 * 这一层是数据库和worker/jober中间的连接层
 * 通过这一层,隔离了数据库操作,数据库可以由这一层抽象出来
 * 实例化不同的数据库类型
 * 这一层还实例化用户指定的类,因此可以
 * 
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kk1987n/LineQue.git
 * @version 1.0.0
 */
class dbJobInstance {

    /**
     * 数据库实例
     * @var type 
     */
    private $dbInstance = null;
    private $procLine = null;

    /**
     * 初始化数据库控制
     */
    public function __construct() {
        $this->dbInstance = $this->doDbInstance();
        $this->procLine = @new ProcLine((preg_match("/cli/i", php_sapi_name()) && defined(LOGPATH) ? LOGPATH : null));
    }

    /**
     * 获取一个Job,只获取不出队
     * @param type $queue
     * @return type
     */
    public function getJob($queue) {
        return $this->dbInstance->getJob($queue);
    }

    /**
     * 出队一个job
     * @param type $queue
     * @return type
     */
    public function popJob($queue) {
        return $this->dbInstance->popJob($queue);
    }

    /**
     * 修改job的状态为running
     * @param type $job
     * @return type
     */
    public function workingOn($job) {
        $this->updateStat(Status::STATUS_WAITING, false); //开始,watting统计-1
        return $this->updateJobStatus($job, Status::STATUS_RUNNING);
    }

    /**
     * 修改job的状态为FAILED
     * @param type $job
     * @return type
     */
    public function workingFail($job) {
        $this->updateStat(Status::STATUS_RUNNING, false); //失败,running统计-1
        return $this->updateJobStatus($job, Status::STATUS_FAILED);
    }

    /**
     * 修改job的状态为COMPLETE
     * @param type $job
     * @return type
     */
    public function workingDone($job) {
        $this->updateStat(Status::STATUS_RUNNING, false); //成功,running统计-1
        return $this->updateJobStatus($job, Status::STATUS_COMPLETE);
    }

    /**
     * 修改job的状态
     * @param type $job
     * @param type $status
     * @return type
     */
    private function updateJobStatus($job, $status) {
        if ($status == Status::STATUS_COMPLETE || $status == Status::STATUS_FAILED) {//成功/失败之后,删除running的job
            $this->dbInstance->delByJobid($job['id'], Status::STATUS_RUNNING);
        }
        $this->updateStat($status);
        return $this->dbInstance->updateJobStatus($job['id'], $status, $job);
    }

    /**
     * lineque:stat用以统计四种状态的数量
     * 这里修改每种数量的增减变化
     * @param type $status
     * @param type $inc
     * @param type $step
     * @return type
     */
    private function updateStat($status, $inc = true, $step = 1) {
        if ($inc) {
            return $this->dbInstance->incrStat($status, $step);
        } else {
            return $this->dbInstance->decrStat($status, $step);
        }
    }

    /**
     * 新增一个job
     * @param type $queue
     * @param type $class
     * @param type $args
     * @return type
     */
    public function addJob($queue, $class, $args = null) {
        if (!$queue) {
            return false;
        }
        if (!$class) {
            return false;
        }
        $this->updateStat(Status::STATUS_WAITING);
        return $this->dbInstance->addJobToList($queue, $class, $args);
    }

    /**
     * 获取队列的前100个（用于做队列排名）
     * @param type $queue
     * @return boolean
     */
    public function get100Jobs($queue) {
        if (!$queue) {
            return false;
        }
        return $this->dbInstance->get100Jobs($queue);
    }

    /**
     * 获取队列的总长度
     * @param type $queue
     * @return type
     */
    public function getListLen($queue) {
        return $this->dbInstance->getListLen($queue);
    }

    /**
     * 实例化不同的数据库,目前默认为redis,后可改为mysql,文件等方式
     * @param type $config
     * @return RedisDb
     */
    public function doDbInstance() {
        $dbConf = Conf::getConfig();
        if ($dbConf) {
            $Dbtype = ucfirst(strtolower($dbConf['DBTYPE']));
            $class = "Yjtec\\Linque\\Lib\\" . $Dbtype . "\\" . $Dbtype . "Db";
            return new $class($dbConf);
        } else {
            die('数据库配置无效');
        }
    }

}
