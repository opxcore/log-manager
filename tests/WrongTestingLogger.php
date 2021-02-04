<?php

class WrongTestingLogger
{
    public array $logs = [];

    public ?string $param;

    public function __construct($param = null)
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