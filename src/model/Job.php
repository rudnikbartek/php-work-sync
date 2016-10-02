<?php
namespace Rdnk\WorkSync\Model;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Rdnk\WorkSync\Config\Config;


/**
 * Class Job
 * Manage jobs, bind them to worker db instances
 * Check and change job statuses
 * Create logfiles for jobs
 * @package Rdnk\WorkSync\Model
 */
class Job {

    CONST STATUS_DONE_WITH_ERRORS = '-2';
    CONST STATUS_DONE_WITH_SUCCESS = '1';
    CONST STATUS_IN_PROGRESS_WITH_ERRORS = '-1';
    CONST STATUS_IN_PROGRESS = '0';

    static $table_name = 'job';

    /**
     * @var \PDO
     */
    public static $connection;

    private $logger = null;


    /**
     * @param $worker_type
     * @param $name
     * @param $work_done
     * @param $work_overal
     * @return Job
     */
    public static function createJob($worker_type, $name, $work_done, $work_overal) {
        $token = self::generateToken();
        return self::create(array(
            'worker_type' => $worker_type,
            'name' => $name,
            'work_done' => $work_done,
            'work_overal' => $work_overal,
            'token' => $token,
            'status' => self::STATUS_IN_PROGRESS,
            'log_file' => Config::$log_path.'_jobs/'.date('Ymd_His').'_'.$token.'.log',
        ));
    }


    public static function clear() {
        self::query('TRUNCATE '.self::table_name() );
    }


    static function generateToken() {
        return sha1(time().uniqid());
    }


    /**
     * @param null $type
     * @param null $limit
     * @param null $status
     * @param bool|false $without_worker
     * @return Job[]
     * @throws Rdnk\ActiveRecord\RecordNotFound
     */
    public static function getJobs($type = null, $limit = null, $status = null, $without_worker = false) {
        if($type === null && $limit === null) {
            return self::all();
        } else {
            $conditions = array('');

            if ($type !== null) {
                $conditions[0] .= 'worker_type = ?';
                $conditions[] = $type;
            }

            if ($status !== null) {
                $conditions[0] .= (empty($conditions[0])?'status = ?' : ' AND status = ?');
                $conditions[] = $status;
            }

            if($without_worker) {
                $conditions[0] .= (empty($conditions[0])?'worker_token IS NULL' : ' AND worker_token IS NULL');
            }

            if (empty($conditions) && $limit !== null) {
                return self::find('all', array('limit'=> $limit));
            } else if($limit === null) {
                return self::find('all', array('conditions'=> $conditions));
            } else {
                return self::find('all', array('conditions'=>$conditions, 'limit'=> $limit));
            }
        }
    }

    /**
     * @return Logger
     */
    public function getLogger() {
        if(!empty($this->logger)) {
            return $this->logger;
        } else {
            $log_path = $this->log_file;
            $this->logger = new Logger('jobsLogger');
            $this->logger->pushHandler(new StreamHandler($log_path, Logger::DEBUG));
            return $this->logger;
        }
    }

    public function changeStatus($status) {
        $this->status = $status;
        $this->save();
    }

    public function logMessage($message_level, $message) {
        $this->getLogger()->log($message_level, $message);
    }

    public function registerWorker(Worker $worker) {

        $query = self::query("UPDATE ".self::table_name()." SET ".self::table_name().".worker_token='".$worker->getToken()."' WHERE ".self::table_name().".id = '".$this->id."' AND ".self::table_name().".worker_token IS NULL");

        if(is_object($query)) {
            if($query->rowCount() == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            false;
        }

    }

    public function getToken() {
        return $this->token;
    }

    public function changeProgress($work_done, $work_overal = null) {
        $this->work_done = $work_done;
        if($work_overal !== null) {
            $this->work_overal = $work_overal;
        }
        $this->save();
    }

    public function setJobMsg($array_msg) {
        $this->job_msg = json_encode($array_msg);
        $this->save();
    }

    public function getJobMsg() {
        return json_decode($this->job_msg);
    }

}
