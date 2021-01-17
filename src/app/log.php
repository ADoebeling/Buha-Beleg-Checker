<?php

namespace DOEBELING\BuHaJournal;

use DateTimeZone;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;


class log extends Logger
{
    public function __construct(string $name, array $handlers = [], array $processors = [], ?DateTimeZone $timezone = null)
    {
        parent::__construct($name, $handlers, $processors, $timezone);

        // TODO Cleanup
        $logHandler[] = new StreamHandler('.log/buhaJounal_debug_' . date("ymd") . '.log', LOGGER::DEBUG);
        $logHandler[] = new StreamHandler('.log/buhaJounal_info_' . date("ymd") . '.log', LOGGER::INFO);

        foreach ($logHandler as $handler)
        {
            $this->pushHandler($handler);
        }
    }

    public function debug($message, $context = []): void
    {
        $this->addRecord(static::DEBUG, (string) $message, $context);
    }

    public function info($message, $context = []): void
    {
        $this->addRecord(static::INFO, (string) $message, $context);
    }

    public function addRecord(int $level, string $message, $context = []): bool
    {
        // If context isn't an array - create one!
        switch (gettype($context))
        {
            case 'string':
                $context = [$context];

            case 'object':
                $contect = (array) $context;
        }
        return parent::addRecord($level, $message, (array) $context);
    }
}