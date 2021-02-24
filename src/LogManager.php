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

use OpxCore\Container\Container;
use OpxCore\Container\Exceptions\ContainerException;
use OpxCore\Container\Exceptions\NotFoundException;
use OpxCore\Log\Exceptions\LogManagerException;
use OpxCore\Log\Interfaces\LogManagerInterface;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

class LogManager extends Container implements LogManagerInterface
{
    /** @var string|array Default logger(s) */
    protected $default;

    /** @var array Loggers definitions */
    protected array $loggers;

    /** @var array|null Groups of loggers */
    protected ?array $groups;

    /**
     * Logger constructor.
     *
     * @param string|array $default
     * @param array $loggers
     * @param array|null $groups
     *
     * @return  void
     */
    public function __construct($default, array $loggers, ?array $groups = null)
    {
        $this->default = $default;
        $this->loggers = $loggers;
        $this->groups = $groups;
    }

    /**
     * System is unusable.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     *
     * @throws  LogManagerException
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger()->emergency($message, $context);
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
     *
     * @throws  LogManagerException
     */
    public function alert($message, array $context = []): void
    {
        $this->logger()->alert($message, $context);
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
     *
     * @throws  LogManagerException
     */
    public function critical($message, array $context = []): void
    {
        $this->logger()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     *
     * @throws  LogManagerException
     */
    public function error($message, array $context = []): void
    {
        $this->logger()->error($message, $context);
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
     *
     * @throws  LogManagerException
     */
    public function warning($message, array $context = []): void
    {
        $this->logger()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     *
     * @throws  LogManagerException
     */
    public function notice($message, array $context = []): void
    {
        $this->logger()->notice($message, $context);
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
     *
     * @throws  LogManagerException
     */
    public function info($message, array $context = []): void
    {
        $this->logger()->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param $message
     * @param array $context
     *
     * @return  void
     *
     * @throws  LogManagerException
     */
    public function debug($message, array $context = []): void
    {
        $this->logger()->debug($message, $context);
    }

    /**
     * Logs with a default logger.
     *
     * @param mixed $level
     * @param $message
     * @param array $context
     *
     * @return void
     *
     * @throws  InvalidArgumentException
     * @throws  LogManagerException
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger()->log($level, $message, $context);
    }

    /**
     * Get logger assigned to name.
     *
     * @param string|array|null $names
     *
     * @return  LoggerInterface
     *
     * @throws  LogManagerException
     */
    public function logger($names = null): LoggerInterface
    {
        $loggerNames = $this->resolveLoggerNames($names);

        $loggers = $this->resolveLoggers($loggerNames);

        return new LoggerProxy($loggers);
    }

    /**
     * Resolve a group of loggers.
     *
     * @param string|array $names
     *
     * @return  LoggerInterface
     *
     * @throws  LogManagerException
     */
    public function group($names): LoggerInterface
    {
        $loggers = [[]];

        foreach ((array)$names as $name) {
            if (!isset($this->groups[$name])) {
                throw new LogManagerException("Group {$name} not found");
            }

            $loggers[] = $this->groups[$name];
        }

        return $this->logger(array_unique(array_merge(...$loggers)));
    }

    /**
     * Detect if default logger has to be used.
     * Convert string to array if single logger given.
     *
     * @param string|array|null $names
     *
     * @return  array
     *
     * @throws  LogManagerException
     */
    protected function resolveLoggerNames($names): array
    {
        if ($names === null) {
            $names = $this->default ?? null;

            if (!$names) {
                throw new LogManagerException('Default logger not assigned.');
            }
        }

        return (array)$names;
    }

    /**
     * Resolve all given names for corresponding loggers.
     *
     * @param array $names
     *
     * @return  array
     *
     * @throws LogManagerException
     */
    protected function resolveLoggers(array $names): array
    {
        $resolved = [];

        foreach ($names as $name) {

            $resolved[] = $this->resolveSingleLogger($name);
        }

        return $resolved;
    }

    /**
     * Get or create logger instance.
     *
     * @param string $name
     *
     * @return LoggerInterface
     *
     * @throws LogManagerException
     */
    protected function resolveSingleLogger(string $name): LoggerInterface
    {
        if ($this->has($name)) {
            try {
                $logger = $this->make($name);

            } catch (ContainerException | NotFoundException $e) {

                throw new LogManagerException("Can not resolve [{$name}]: {$e->getMessage()}.", 0, $e);
            }

            if (!($logger instanceof LoggerInterface)) {
                throw new LogManagerException("[{$name}] must implement interface Psr\Log\LoggerInterface");
            }

            return $logger;
        }

        $config = $this->loggers[$name] ?? null;

        if (!$config) {
            throw new LogManagerException("Configuration for logger [{$name}] not found.");
        }

        $loggerClass = $config['driver'] ?? null;

        if (!$loggerClass) {
            throw new LogManagerException("Logger not set for [{$name}].");
        }

        unset($config['driver']);

        $logger = $this->makeLogger($loggerClass, $config);

        $this->instance($name, $logger);

        return $logger;
    }

    /**
     * Build logger instance.
     *
     * @param string $name
     * @param array $parameters
     *
     * @return  LoggerInterface
     *
     * @throws LogManagerException
     */
    protected function makeLogger(string $name, array $parameters): LoggerInterface
    {
        try {
            $logger = $this->make($name, $parameters);

            if (!$logger instanceof LoggerInterface) {
                throw new LogManagerException("[$name] must be instance of [Psr\Log\LoggerInterface].");
            }

            return $logger;

        } catch (ContainerException | NotFoundException $e) {

            throw new LogManagerException("Can not create log for [{$name}]: {$e->getMessage()}", 0, $e);
        }
    }
}