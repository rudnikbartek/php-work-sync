<?php
namespace Rdnk\WorkSync;
use Rdnk\WorkSync\Model\Job;

/**
 * Class SampleGenerator
 * TODO: Show how to use Sync between workers and jobs
 * @package Rdnk\WorkSync\Model
 */
class SampleGenerator
{
    public $worker;

    public $locks=array();

    public function performGenerationJob(Job $job)
    {

    }

}