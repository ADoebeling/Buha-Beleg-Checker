<?php

declare(strict_types=1);

namespace DOEBELING\buhaJournal;
error_reporting(E_ALL);


use Cz\Git\GitException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

require_once 'vendor/monolog/monolog/src/Monolog/Logger.php';

class gitRepository extends \Cz\Git\GitRepository
{
    /**
     * @var Logger
     */
    protected $log;

    public function __construct($repository, array $log)
    {
        $this->setLog($log);
        $this->log->debug(__METHOD__, [$repository]);
        return parent::__construct($repository);
    }

    public function setLog(array $log)
    {
        $this->log = new log("gitRepository");
        foreach ($log as $handler) $this->log->pushHandler($handler);
        return $this;
    }

    public function __call($name, $arguments)
    {
        $this->log->debug(__CLASS__."::$name", $arguments);
        return parent::{$name}($arguments);
    }

    public function __destruct()
    {
        $this->log->debug(__METHOD__);
        if (function_exists('parent::__destruct'))
        {
            parent::__destruct();
        }
    }
}