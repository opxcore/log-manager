<?php

namespace OpxCore\Log;

use OpxCore\Container\Container;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogManager extends AbstractLogger
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
     * Logs with a default driver.
     *
     * @param  mixed $level
     * @param  string $message
     * @param  array $context
     *
     * @return void
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
     * @throws  \Psr\Log\InvalidArgumentException
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
     * @throws  \Psr\Log\InvalidArgumentException
     */
    protected function resolveDriverName($name): string
    {
        if ($name === null) {
            $name = $this->config['default'] ?? null;

            if (!$name) {
                throw new InvalidArgumentException('Default log driver not assigned.');
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
     * @throws  \Psr\Log\InvalidArgumentException
     */
    protected function resolveDriver($name): LoggerInterface
    {
        if ($this->container->has($name)) {
            try {
                $driver = $this->container->make($name);
            } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

                throw new InvalidArgumentException("Con not resolve [{$name}].", 0, $e);
            }

            return $driver;
        }

        $config = $this->config['drivers'][$name] ?? null;

        if (!$config) {
            throw new InvalidArgumentException("Configuration for driver [{$name}] not found.");
        }

        $driverClass = $config['driver'] ?? null;

        if (!$driverClass) {
            throw new InvalidArgumentException("Driver not set for [{$name}].");
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
     * @throws  \Psr\Log\InvalidArgumentException
     */
    protected function makeDriver($name, $parameters): LoggerInterface
    {
        try {
            $driver = $this->container->make($name, $parameters);

            if (!$driver instanceof LoggerInterface) {
                throw new InvalidArgumentException("[$name] must be instance of [Psr\Log\LoggerInterface].");
            }

            return $driver;

        } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

            throw new InvalidArgumentException("Con not log create driver for [{$name}].", 0, $e);
        }
    }
}