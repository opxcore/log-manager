<?php

namespace OpxCore\Log;

use OpxCore\Container\Container;
use OpxCore\Log\Exceptions\LogManagerException;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager implements LoggerInterface
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Container for drivers.
     *
     * @var \OpxCore\Container\Container
     */
    protected $container;

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
        $this->container = new Container();
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
        if (!in_array($level, [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ], true)) {

            throw new InvalidArgumentException("You should not use log level [{$level}]");
        }

        $this->driver()->log($level, $message, $context);
    }

    /**
     * Get logger assigned with channel name.
     *
     * @param  string|null $name
     *
     * @return  \Psr\Log\LoggerInterface
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    public function driver($name = null): LoggerInterface
    {
        $driverName = $this->resolveDriverName($name);

        return $this->resolveDriver($driverName);
    }

    /**
     * Resolve driver name to be used.
     *
     * @param  string|null $name
     *
     * @return  string
     *
     * @throws  \OpxCore\Log\Exceptions\LogManagerException
     */
    protected function resolveDriverName($name): string
    {
        if ($name === null) {
            $name = $this->config['default'] ?? null;

            if (!$name) {
                throw new LogManagerException('Default log driver not assigned.');
            }
        }

        return $name;
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
    protected function resolveDriver($name): LoggerInterface
    {
        if ($this->container->has($name)) {
            try {
                $driver = $this->container->make($name);

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

        $this->container->instance($name, $driver);

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
            $driver = $this->container->make($name, $parameters);

            if (!$driver instanceof LoggerInterface) {
                throw new LogManagerException("[$name] must be instance of [Psr\Log\LoggerInterface].");
            }

            return $driver;

        } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

            throw new LogManagerException("Con not log create driver for [{$name}].", 0, $e);
        }
    }
}