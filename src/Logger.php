<?php

namespace OpxCore\Log;

use OpxCore\Container\Container;
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


    protected $resolver;

    /**
     * Logger constructor.
     *
     * @param  array $config
     *
     * @return  void
     */
    public function __construct($config)
    {
        $this->resolver = new Container();
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
        if ($this->resolver->has($name)) {
            try {
                $driver = $this->resolver->make($name);
            } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

                throw new InvalidArgumentException("Con not create [{$name}].", 0, $e);
            }

            return $driver;
        }

        $config = $this->config['channels'][$name] ?? null;

        if (!$config) {
            throw new InvalidArgumentException("Configuration for channel {$name} not found.");
        }

        $driverClass = $config['driver'];

        unset($config['driver']);

        $driver = $this->makeDriver($driverClass, $config);

        $this->resolver->instance($name, $driver);

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
            $driver = $this->resolver->make($name, $parameters);

            if (!$driver instanceof LoggerInterface) {
                throw new InvalidArgumentException("[$name] must be instance of [Psr\Log\LoggerInterface].");
        }

        return $driver;

        } catch (\OpxCore\Container\Exceptions\ContainerException|\OpxCore\Container\Exceptions\NotFoundException $e) {

            throw new InvalidArgumentException("Con not create [{$name}].", 0, $e);
        }
    }
}