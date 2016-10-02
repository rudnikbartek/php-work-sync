<?php
namespace Rdnk\WorkSync\Model;

class Worker {

    CONST STATUS_START = 0;
    CONST STATUS_WORKING = 1;
    CONST STATUS_IDLE = 2;
    CONST STATUS_SUCCESS_EXIT = 3;
    CONST STATUS_WORK_FAILURE = -1;
    CONST STATUS_OVERALL_FAILURE = -2;

    CONST SIGNAL_KEEP_WORKING = 0;
    CONST SIGNAL_STOP_WORKING = 1;

    static $table_name = 'worker';
    public static $connection;

    private $logger = null;

    /**
     * @param $type
     * @return Worker
     */
    static function createNewWorker($type) {
        $token = self::generateToken();
        return self::create(array(
            'token' => $token,
            'type'  => $type,
            'status' => self::STATUS_START,
            'signal' => self::SIGNAL_KEEP_WORKING,
            'log_file' => App::$log_path.'_workers/'.date('Ymd_His').'_'.$token.'.log',
            'last_seen' => time(),
        ));
    }

    static function generateToken() {
        return sha1(time().uniqid());
    }

    static function getWorkersWithStatus($status) {
        return self::find('all', array('conditions'=>array('status = ?', $status)));
    }

    /**
     * @return Logger
     */
    public function getLogger() {
        if(!empty($this->logger)) {
            return $this->logger;
        } else {
            $log_path = $this->log_file;
            $this->logger = new Logger('workersLogger');
            $this->logger->pushHandler(new StreamHandler($log_path, Logger::DEBUG));
            return $this->logger;
        }
    }

    public function setStatus($status) {
        $this->status = $status;
        $this->save();
    }

    public function getToken() {
        return $this->token;
    }

}
