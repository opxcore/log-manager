<?php
/*
 * This file is part of the OpxCore.
 *
 * Copyright (c) Lozovoy Vyacheslav <opxcore@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpxCore\Log;

use OpxCore\Log\Interfaces\LoggerInterface;

class LoggerProxy implements LoggerInterface
{
    protected $loggers;

    /**
     * LoggerProxy constructor.
     *
     * @param LoggerInterface|array $loggers
     *
     * @return  void
     */
    public function __construct($loggers)
    {
        $this->loggers = $loggers;
    }

    /**
     * System is unusable.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function emergency($message, array $context = []): void
    {
        $this->callAction('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function alert($message, array $context = []): void
    {
        $this->callAction('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function critical($message, array $context = []): void
    {
        $this->callAction('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function error($message, array $context = []): void
    {
        $this->callAction('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function warning($message, array $context = []): void
    {
        $this->callAction('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function notice($message, array $context = []): void
    {
        $this->callAction('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function info($message, array $context = []): void
    {
        $this->callAction('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function debug($message, array $context = []): void
    {
        $this->callAction('debug', $message, $context);
    }

    /**
     * Logs with a default driver.
     *
     * @param mixed $level
     * @param $message
     * @param array $context
     *
     * @return  void
     */
    public function log($level, $message, array $context = []): void
    {
        $this->callAction('log', $level, $message, $context);
    }

    /**
     * Call log action for each registered logger.
     *
     * @param  $method
     * @param mixed ...$parameters
     *
     * @return  void
     */
    protected function callAction($method, ...$parameters): void
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        foreach ((array)$this->loggers as $logger) {

            $logger->$method(...$parameters);
        }
    }
}