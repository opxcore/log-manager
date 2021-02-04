<?php

use OpxCore\Log\Exceptions\LogManagerException;
use OpxCore\Log\LogManager;
use PHPUnit\Framework\TestCase;

class LogManagerTest extends TestCase
{
    protected array $config = [
        'default' => 'testing',
        'loggers' => [
            'testing' => [
                'driver' => TestingLogger::class,
            ],
        ],
    ];

    public static function setUpBeforeClass(): void
    {
        require __DIR__ . '/TestingLogger.php';
        require __DIR__ . '/WrongTestingLogger.php';
    }

    public function testNoConfig(): void
    {
        $logger = new LogManager(null, []);

        $this->expectException(LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function testNoDriver(): void
    {
        $logger = new LogManager('testing', []);

        $this->expectException(LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');

    }

    public function testNormal(): void
    {
        $logger = new LogManager('testing',
            [
                'testing1' => [
                    'driver' => TestingLogger::class,
                    'param' => 'Testing'
                ],
            ]);

        $driver1 = new TestingLogger('testing 1');
        $driver2 = new TestingLogger('testing 2');

        $logger->bind('testing', function () use ($driver1) {
            return $driver1;
        });

        $logger->bind('testing2', function () use ($driver2) {
            return $driver2;
        });

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::DEBUG, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->emergency('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::EMERGENCY, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->alert('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::ALERT, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->critical('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::CRITICAL, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->error('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::ERROR, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->warning('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::WARNING, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->notice('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::NOTICE, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->info('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::INFO, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];

        $logger->debug('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::DEBUG, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        $driver1->logs = [];
        $driver2->logs = [];

        $logger->driver(['testing1', 'testing2', 'testing'])->debug('Test');
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::DEBUG, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::DEBUG, 'message' => 'Test', 'context' => []]],
            $driver2->logs
        );
    }

    public function testNoGroup(): void
    {
        $logger = new LogManager('testing', [], [
            'local' => ['testing1', 'testing2'],
        ]);

        $driver1 = new TestingLogger('testing 1');
        $driver2 = new TestingLogger('testing 2');

        $logger->bind('testing1', function () use ($driver1) {
            return $driver1;
        });

        $logger->bind('testing2', function () use ($driver2) {
            return $driver2;
        });

        $this->expectException(LogManagerException::class);

        $logger->group('nogroup')->debug('Test');
    }

    public function testGroup(): void
    {
        $logger = new LogManager('testing', [], [
            'local' => ['testing1', 'testing2'],
        ]);

        $driver1 = new TestingLogger('testing 1');
        $driver2 = new TestingLogger('testing 2');

        $logger->bind('testing1', function () use ($driver1) {
            return $driver1;
        });

        $logger->bind('testing2', function () use ($driver2) {
            return $driver2;
        });

        $logger->group('local')->debug('Test');

        self::assertEquals(
            [['level' => Psr\Log\LogLevel::DEBUG, 'message' => 'Test', 'context' => []]],
            $driver1->logs
        );
        self::assertEquals(
            [['level' => Psr\Log\LogLevel::DEBUG, 'message' => 'Test', 'context' => []]],
            $driver2->logs
        );
    }

    public function testWrongLogger(): void
    {
        $logger = new LogManager('testing',
            [
                'testing' => [
                    'driver' => WrongTestingLogger::class,
                    'param' => 'Testing'
                ],
            ]);

        $this->expectException(LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function testMakeException(): void
    {
        $logger = new LogManager(null, []);
        $logger->bind('test', function () {
            return new WrongTestingLogger;
        });

        $this->expectException(LogManagerException::class);

        $logger->driver('test')->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function testDriverNotFound(): void
    {
        $logger = new LogManager('testing',
            [
                'testing' => [
                    'driver' => 'NotTestingLogger',
                ],
            ]);

        $this->expectException(LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function testDriverNoParam(): void
    {
        $logger = new LogManager('testing', [
            'testing' => [
                'driver' => TestingLogger::class,
            ],
        ]);

        $this->expectException(LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }

    public function testNoDriverClass(): void
    {
        $logger = new LogManager('testing', [
            'testing' => [
                'driver' => '',
                'param' => 'Testing'
            ],
        ]);

        $this->expectException(LogManagerException::class);

        $logger->log(Psr\Log\LogLevel::DEBUG, 'Test');
    }
}
