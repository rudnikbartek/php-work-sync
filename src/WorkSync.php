<?php
namespace Rdnk\WorkSync;

use Rdnk\WorkSync\Config\Config;
use Rdnk\WorkSync\Model\Job;
use Rdnk\WorkSync\Model\SyncCache;
use Rdnk\WorkSync\Model\Worker;


/**
 * Class WorkSync
 * Base work management class
 * @package Rdnk\WorkSync
 */
class WorkSync {

    /**
     * Injected configuration
     * @var Config
     */
    private $config;

    /**
     * This connection is shared with all model instances
     * @var \PDO
     */
    public  $connection;

    /**
     * @var Job
     * Creating new jobs and creating queues
     */
    private $job_model;

    /**
     * @var SyncCache
     * Syncing values between workers
     */
    private $sync_cache_model;

    /**
     * @var Worker
     * Controlling workers
     */
    private $worker_model;


    public function createJob() {

    }

    public function createWorker() {

    }




}