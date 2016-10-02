<?php
namespace Rdnk\WorkSync;

use League\CLImate\CLImate;
use Monolog\Logger;
use Rdnk\WorkSync\Config\Config;
use Rdnk\WorkSync\Model\Job;
use Rdnk\WorkSync\Model\Worker as WorkerModel;


class Worker {

    /**
     * Quit signals
     */
    CONST QUIT_SUCCESS = '1';
    CONST QUIT_WITH_ERRORS = '-1';
    CONST QUIT_USER_SIGNAL = '0';
    // ------------------------------------

    /**
     * Worker name defined inside child class
     * @var string
     */
    public static $worker_name = '';

    /**
     * Injected config
     * @var Config
     */
    private $config;

    /**
     * Worker description
     * @var string
     */
    public static $worker_hello_text = '';

    /**
     * Worker type (MasterWorker DB type)
     * @var string
     */
    public static $worker_type = '';

    /**
     * Seconds between job checks
     * @var int
     */
    public static $check_interval = 30;

    /**
     * Db model of worker
     * @var \Rdnk\WorkSync\Model\Worker
     */
    public $worker;

    /**
     * Managing cmd output
     * league/CLImate
     * @var CLImate
     */
    public $climate;

    /**
     * Monolog logger
     * @var Logger
     */
    public $logger;


    /**
     * @param WorkerModel $worker
     */
    public function bindDbWorker(WorkerModel $worker) {
        $this->worker = $worker;
        $this->worker->setStatus(WorkerModel::STATUS_IDLE);

    }

    /**
     * @param Logger|null $logger
     */
    public function bindLogger(Logger $logger = null) {
        if(is_null($logger)) {
            $this->logger = $this->worker->getLogger();
        } else {
            $this->logger = $logger;
        }
    }

    public function isQuitSignalSend() {
        $this->worker->reload();
        if($this->worker->signal == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Worker constructor.
     * Inject global configuration object
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->climate = $this->config->getCLImate();
        $this->logMessages(array("","worker initializing..."));
        $this->bindDbWorker(WorkerModel::createNewWorker(static::$worker_type));
        $this->logMessages(array("","db_item binded with token: [".$this->worker->token.']'));
        $this->bindLogger();
        $this->logMessages(array("","Logger binded. From now all console date go to file: [".$this->worker->log_file.']',""));
        $this->createHelloMessage(true);
    }

    /**
     * Write hello message
     * @param bool $echo_to_screen
     */
    public function createHelloMessage($echo_to_screen = true) {

        $message = array(
                    '####################################################################',
                    '##### '.$this->config->app_name.' version: '.$this->config->getVersion(),
                    '##    WORKER: '.static::$worker_name.' ',
                    '##            '.static::$worker_hello_text.' ',
                    '##### App modification date: '.$this->config->getModDate().' ENV: '.strtoupper($this->config->getEnvironment()),
                    '####################################################################'
                );

        $this->logMessages($message);

    }

    /**
     * Store messages to logger
     * @param $messages_array
     * @param bool $echo_to_console
     * @throws \Exception
     */
    public function logMessages($messages_array, $echo_to_console = true) {
        if(!is_array($messages_array)) {
            throw new \Exception('You must provide messages in array.');
        }

        foreach($messages_array as $message_line) {
            if(!empty($this->logger)) {
                $this->logger->addInfo($message_line);
            }

            if($echo_to_console === true) {
                $this->climate->out($message_line);
            }
        }
    }


    /**
     * Close worker using status
     * @param $quit_type
     */
    public function quit($quit_type) {
        switch($quit_type) {
            case self::QUIT_SUCCESS:
                $this->worker->setStatus(WorkerModel::STATUS_SUCCESS_EXIT);
                exit(0);
                break;

            case self::QUIT_USER_SIGNAL:
                $this->worker->setStatus(WorkerModel::STATUS_WORK_FAILURE);
                exit(0);
                break;

            case self::QUIT_WITH_ERRORS:
                $this->worker->setStatus(WorkerModel::STATUS_OVERALL_FAILURE);
                exit(0);
                break;
        }
    }

    /**
     * Check signal from db instance
     * it can close worker process
     * @return bool
     */
    public function checkSignal() {

        // GET NEWEST WORKER ENTITY FROM DB
        $this->worker->reload();

        switch ($this->worker->signal) {
            case WorkerModel::SIGNAL_KEEP_WORKING:
                return true;
                break;
            case WorkerModel::SIGNAL_STOP_WORKING:
                $this->quit(self::QUIT_USER_SIGNAL);
                break;
            default:
                return true;
                break;
        }
    }


    /**
     * Start worker loop
     */
    public function start() {

        while(true) {
            $job = $this->getJob();
            if($job === false) {
                $this->wait();
            } else {
                $this->performJob($job);
            }
        }

    }

    /**
     * Gets job from registered queue and register to it worker
     * check if registration was perform correctly
     * (!important this prevent from situation where many workers perform same job)
     * @return bool|mixed|Job
     */
    public function getJob() {
        $jobs = Job::getJobs(static::$worker_type, null, null, true);

        if(!empty($jobs) && is_array($jobs)) {
            $job = $jobs[0];
            $job->registerWorker($this->worker);
            return $job;
        }

        return false;

    }

    /**
     * Placeholder that keep your work hold
     * @param Job $job
     */
    public function performJob(Job $job) {
        // HERE IMPLEMENTATION OF JOB
        var_dump($job);
    }


    /**
     * Wait function for main loop
     * check worker signal and close or sleep
     */
    public function wait() {
        $this->checkSignal();
        sleep(static::$check_interval);
        $this->logMessages(array('Check interval performed '.date('H:i:s')));
    }



}