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
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

class LogManager extends Container implements LoggerInterface
{
    /** @var string|array Default log driver(s) */
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
        $this->driver()->emergency($message, $context);
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
        $this->driver()->alert($message, $context);
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
        $this->driver()->critical($message, $context);
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
        $this->driver()->error($message, $context);
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
        $this->driver()->warning($message, $context);
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
        $this->driver()->notice($message, $context);
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
        $this->driver()->info($message, $context);
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
        $this->driver()->debug($message, $context);
    }

    /**
     * Logs with a default driver.
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
        $this->driver()->log($level, $message, $context);
    }

    /**
     * Get logger assigned with channel name.
     *
     * @param string|array|null $names
     *
     * @return  LoggerInterface
     *
     * @throws  LogManagerException
     */
    public function driver($names = null): LoggerInterface
    {
        $driverNames = $this->resolveDriverNames($names);

        $drivers = $this->resolveDrivers($driverNames);

        return new LoggerProxy($drivers);
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
        $drivers = [[]];

        foreach ((array)$names as $name) {
            if (!isset($this->groups[$name])) {
                throw new LogManagerException("Group {$name} not found");
            }

            $drivers[] = $this->groups[$name];
        }

        return $this->driver(array_unique(array_merge(...$drivers)));
    }

    /**
     * Detect if default driver has to be used. Additionally convert string to
     * array if single driver given.
     *
     * @param string|array|null $names
     *
     * @return  array
     *
     * @throws  LogManagerException
     */
    protected function resolveDriverNames($names): array
    {
        if ($names === null) {
            $names = $this->default ?? null;

            if (!$names) {
                throw new LogManagerException('Default log driver not assigned.');
            }
        }

        return (array)$names;
    }

    /**
     * Resolve all given names for corresponding drivers.
     *
     * @param array $names
     *
     * @return  array
     *
     * @throws LogManagerException
     */
    protected function resolveDrivers(array $names): array
    {
        $resolved = [];

        foreach ($names as $name) {

            $resolved[] = $this->resolveSingleDriver($name);
        }

        return $resolved;
    }

    /**
     * Get or create driver instance.
     *
     * @param string $name
     *
     * @return LoggerInterface
     *
     * @throws LogManagerException
     */
    protected function resolveSingleDriver(string $name): LoggerInterface
    {
        if ($this->has($name)) {
            try {
                $driver = $this->make($name);

            } catch (ContainerException | NotFoundException $e) {

                throw new LogManagerException("Can not resolve [{$name}]: {$e->getMessage()}.", 0, $e);
            }

            if (!($driver instanceof LoggerInterface)) {
                throw new LogManagerException("[{$name}] must implement interface Psr\Log\LoggerInterface");
            }

            return $driver;
        }

        $config = $this->loggers[$name] ?? null;

        if (!$config) {
            throw new LogManagerException("Configuration for driver [{$name}] not found.");
        }

        $driverClass = $config['driver'] ?? null;

        if (!$driverClass) {
            throw new LogManagerException("Driver not set for [{$name}].");
        }

        unset($config['driver']);

        $driver = $this->makeDriver($driverClass, $config);

        $this->instance($name, $driver);

        return $driver;
    }

    /**
     * Build driver instance.
     *
     * @param string $name
     * @param array $parameters
     *
     * @return  LoggerInterface
     *
     * @throws LogManagerException
     */
    protected function makeDriver(string $name, array $parameters): LoggerInterface
    {
        try {
            $driver = $this->make($name, $parameters);

            if (!$driver instanceof LoggerInterface) {
                throw new LogManagerException("[$name] must be instance of [Psr\Log\LoggerInterface].");
            }

            return $driver;

        } catch (ContainerException | NotFoundException $e) {

            throw new LogManagerException("Con not log create driver for [{$name}]: {$e->getMessage()}", 0, $e);
        }
    }
}