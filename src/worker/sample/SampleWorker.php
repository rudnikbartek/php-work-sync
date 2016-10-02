<?php
namespace Rdnk\WorkSync;

use Rdnk\WorkSync\Config\Config;
use Rdnk\WorkSync\Model\Job;
use Rdnk\WorkSync\Model\Worker as WorkerModel;

/**
 * Class SampleWorker
 * Sample of use with external generator class
 * @package Rdnk\WorkSync\Model
 */
class SampleWorker extends Worker 
{

    public static $worker_name = '';
    public static $worker_hello_text = '';
    public static $sleep_time = 20; // SECONDS

    public static $worker_type = 'Generator';

    public $generator;



    public function __construct(Config $config)
    {
        parent::__construct($config);
        $this->generator = new SampleGenerator($this);
    }

    public function start() {

        $continue_flag = true;
        while($continue_flag) {

            if($this->isQuitSignalSend()) {
                $continue_flag = false;
                $this->quit(Worker::QUIT_USER_SIGNAL);
            }

            $job = $this->getJob();
            if($job) {
                $this->performJob($job);
            }

            sleep(self::$sleep_time);
        }

    }

    public function getJob() {
        $jobs = Job::getJobs(self::$worker_type, null, null, true);

        if(!empty($jobs) && is_array($jobs)) {
            $job = $jobs[0];
            return $job;
        }

        return false;

    }

    public function performJob(Job $job) {
        $register_status = $job->registerWorker($this->worker);
        if($register_status === true) {
            $this->generator->performGenerationJob($job);
        }
    }

    public function quit($quit_type) {
        switch($quit_type) {
            case Worker::QUIT_SUCCESS:
                $this->worker->setStatus(WorkerModel::STATUS_SUCCESS_EXIT);
                exit(0);
                break;

            case Worker::QUIT_USER_SIGNAL:
                $this->worker->setStatus(WorkerModel::STATUS_WORK_FAILURE);
                exit(0);
                break;

            case Worker::QUIT_WITH_ERRORS:
                $this->worker->setStatus(WorkerModel::STATUS_OVERALL_FAILURE);
                exit(0);
                break;
        }
    }
}