<?php

namespace OpxCore\Log;

use OpxCore\Interfaces\LogManagerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

class Logger extends AbstractLogger implements LogManagerInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Loggers instances keyed by channel name.
     *
     * @var array
     */
    protected $channels = [];

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
        $this->channel()->log($level, $message, $context);
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
    public function channel($name = null): LoggerInterface
    {
        $channelName = $this->resolveChannelName($name);

        return $this->resolveChannel($channelName);
    }

    /**
     * Resolve channel name to be used.
     *
     * @param  string|null $name
     *
     * @return  string
     *
     * @throws  \Psr\Log\InvalidArgumentException
     */
    protected function resolveChannelName($name): string
    {
        if ($name === null) {
            $name = $this->config['default'] ?? null;

            if (!$name) {
                throw new InvalidArgumentException('Default channel not assigned.');
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
    protected function resolveChannel($name): LoggerInterface
    {
        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        $config = $this->config['channels'][$name] ?? null;

        if (!$config) {
            throw new InvalidArgumentException("Configuration for channel {$name} not found.");
        }

        $driverClass = $config['driver'];

        unset($config['driver']);

        $driver = $this->makeDriver($driverClass, $config);

        $this->channels[$name] = $driver;

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
            $reflection = new \ReflectionClass($name);

            if (!$reflection->isInstantiable()) {
                throw new InvalidArgumentException("[{$name}] is not instantiable");
            }

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return new $name;
            }

            $expectedArguments = $constructor->getParameters();

            $arguments = [];

            foreach ($expectedArguments as $argument) {
                if (!isset($parameters[$argument->name]) && (!$argument->isDefaultValueAvailable())) {
                    throw new InvalidArgumentException("Can not create [{$name}]. Parameter [{$argument->name} is required.]");
                }

                $arguments[] = $parameters[$argument->name] ?? $argument->getDefaultValue();
            }

            $driver = $reflection->newInstanceArgs($arguments);

            if (!$driver instanceof LoggerInterface) {
                throw new InvalidArgumentException("[$name] must be instance of [Psr\Log\LoggerInterface].");
            }

            return $driver;

        } catch (\ReflectionException $e) {

            throw new InvalidArgumentException("Con not create [{$name}].", 0, $e);
        }
    }
}