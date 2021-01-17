<?php

namespace DOEBELING\buhaJournal;
class log extends \Monolog\Logger
{
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