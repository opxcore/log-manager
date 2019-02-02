<?php

namespace OpxCore\Log;

use OpxCore\Container\Container;
use OpxCore\Log\Exceptions\LogManagerException;
use Psr\Log\LoggerInterface;

class LogManager extends Container implements LoggerInterface
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Logger constructor.
     *
     * @param  array $config
     *
     * @return  void
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * System is unusable.
     *
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
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
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
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
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function critical($message, array $context = []): void
    {
        $this->driver()->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
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
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function warning($message, array $context = []): void
    {
        $this->driver()->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
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
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function info($message, array $context = []): void
    {
        $this->driver()->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param  string $message
     * @param  array $context
     *
     * @return  void
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function debug($message, array $context = []): void
    {
        $this->driver()->debug($message, $context);
    }

    /**
     * Logs with a default driver.
     *
     * @param  mixed $level
     * @param  string $message
     * @param  array $context
     *
     * @return void
     *
     * @throws  \Psr\Log\InvalidArgumentException
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function log($level, $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $context);
    }

    /**
     * Get logger assigned with channel name.
     *
     * @param  string|array|null $names
     *
     * @return  \Psr\Log\LoggerInterface
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function driver($names = null): LoggerInterface
    {
        $driverNames = $this->resolveDriverNames($names);

        $drivers = $this->resolveDrivers($driverNames);

        return new LoggerProxy($drivers);
    }

    /**
     * Detect if default driver has to be used. Additionally convert string to
     * array if single driver given.
     *
     * @param  string|array|null $names
     *
     * @return  array
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    protected function resolveDriverNames($names): array
    {
        if ($names === null) {
            $names = $this->config['default'] ?? null;

            if (!$names) {
                throw new LogManagerException('Default log driver not assigned.');
            }
        }

        return (array)$names;
    }

    /**
     * Resolve all given names for corresponding drivers.
     *
     * @param  array $names
     *
     * @return  array
     *
     * @throws \OpxCore\Log\Exceptions\LogManagerException
     */
    protected function resolveDrivers($names): array
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
     * @param  string $name
     *
     * @return \Psr\Log\LoggerInterface
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    protected function resolveSingleDriver($name): LoggerInterface
    {
        if ($this->has($name)) {
            try {
                $driver = $this->make($name);

            } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

                throw new LogManagerException("Can not resolve [{$name}].", 0, $e);
            }

            return $driver;
        }

        $config = $this->config['loggers'][$name] ?? null;

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
     * @param  string $name
     * @param  array $parameters
     *
     * @return  \Psr\Log\LoggerInterface
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    protected function makeDriver($name, $parameters): LoggerInterface
    {
        try {
            $driver = $this->make($name, $parameters);

            if (!$driver instanceof LoggerInterface) {
                throw new LogManagerException("[$name] must be instance of [Psr\Log\LoggerInterface].");
            }

            return $driver;

        } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

            throw new LogManagerException("Con not log create driver for [{$name}].", 0, $e);
        }
    }
}