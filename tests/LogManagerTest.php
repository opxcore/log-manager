<?php

use PHPUnit\Framework\TestCase;

class LogManagerTest extends TestCase
{
    protected $config = [
        'default' => 'testing',
        'loggers' => [
            'testing' => [
                'driver' => TestingLogger::class,
            ],
        ],
    ];

    public static function setUpBeforeClass()
    {
        require __DIR__ . '/TestingLogger.php';
        require __DIR__ . '/WrongTestingLogger.php';
    }

    public function test_No_Config(): void
    {
        $logger = new \OpxCore\Log\LogManager([]);

        $this->expectException(\OpxCore\Log\Exceptions\LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function test_No_Driver(): void
    {
        $logger = new \OpxCore\Log\LogManager(['default' => 'testing', 'loggers' => []]);

        $this->expectException(\OpxCore\Log\Exceptions\LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');

    }

    public function test_Normal(): void
    {
        $logger = new \OpxCore\Log\LogManager([
            'default' => 'testing',
            'loggers' => [
                'testing' => [
                    'driver' => TestingLogger::class,
                    'param' => 'Testing'
                ],
            ],
        ]);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');

        $driver = $logger->driver();

        $this->assertEquals([[
            'level' => Psr\Log\LogLevel::DEBUG,
            'message' => 'Test',
            'context' => [],
        ]], $driver->logs);
    }

    public function test_Wrong_Logger(): void
    {
        $logger = new \OpxCore\Log\LogManager([
            'default' => 'testing',
            'loggers' => [
                'testing' => [
                    'driver' => WrongTestingLogger::class,
                    'param' => 'Testing'
                ],
            ],
        ]);

        $this->expectException(\OpxCore\Log\Exceptions\LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function test_Normal_Wrong_Level(): void
    {
        $logger = new \OpxCore\Log\LogManager([
            'default' => 'testing',
            'loggers' => [
                'testing' => [
                    'driver' => TestingLogger::class,
                ],
            ],
        ]);

        $this->expectException(\Psr\Log\InvalidArgumentException::class);

        $logger->log('wrong', 'Test');
    }

    public function test_Driver_Not_Found(): void
    {
        $logger = new \OpxCore\Log\LogManager([
            'default' => 'testing',
            'loggers' => [
                'testing' => [
                    'driver' => NotTestingLogger::class,
                ],
            ],
        ]);

        $this->expectException(\OpxCore\Log\Exceptions\LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function test_Driver_No_Param(): void
    {
        $logger = new \OpxCore\Log\LogManager([
            'default' => 'testing',
            'loggers' => [
                'testing' => [
                    'driver' => TestingLogger::class,
                ],
            ],
        ]);

        $this->expectException(\OpxCore\Log\Exceptions\LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function test_No_Driver_Class(): void
    {
        $logger = new \OpxCore\Log\LogManager([
            'default' => 'testing',
            'loggers' => [
                'testing' => [
                    'driver' => '',
                    'param' => 'Testing'
                ],
            ],
        ]);

        $this->expectException(\OpxCore\Log\Exceptions\LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }
}
