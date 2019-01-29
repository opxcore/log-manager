<?php

namespace OpxCore\Log\Channels;

use Psr\Log\AbstractLogger;

class FileWriter extends AbstractLogger
{
    protected $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed $level
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     */
    public function log($level, $message, array $context = []): void
    {
        echo "{$level}: {$message}";
    }
}