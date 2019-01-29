<?php

namespace OpxCore\Log\Channels;

use OpxCore\Log\MessageFormatter;
use Psr\Log\AbstractLogger;

class FileWriter extends AbstractLogger
{
    use MessageFormatter;

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
        $formatted = $this->interpolateMessage($message, $context);

        echo "{$level}: {$formatted}";
    }
}