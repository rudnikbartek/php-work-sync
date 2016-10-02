<?php
// Bootstrap APP
require_once __DIR__.'/../../../vendor/autoload.php';

$worker = new \Rdnk\WorkSync\SampleWorker();

$worker->start();
