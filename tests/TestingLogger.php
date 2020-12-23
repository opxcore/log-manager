<?php

use Psr\Log\AbstractLogger;

class TestingLogger extends AbstractLogger
{
    public array $logs = [];

    public string $param;

    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}