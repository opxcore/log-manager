# Log manager
[![Build Status](https://travis-ci.org/opxcore/log-manager.svg?branch=master)](https://travis-ci.org/opxcore/log-manager)
[![Coverage Status](https://coveralls.io/repos/github/opxcore/log-manager/badge.svg?branch=master)](https://coveralls.io/github/opxcore/log-manager?branch=master)
[![Latest Stable Version](https://poser.pugx.org/opxcore/log-manager/v/stable)](https://packagist.org/packages/opxcore/log-manager)
[![Total Downloads](https://poser.pugx.org/opxcore/log-manager/downloads)](https://packagist.org/packages/opxcore/log-manager)
[![License](https://poser.pugx.org/opxcore/log-manager/license)](https://packagist.org/packages/opxcore/log-manager)

Log manager is package to handle multiple loggers. All write (send or something else) 
logic and message formatting must be implemented in these loggers. Log manager is only
resolves which logger will be used and fires log action for corresponding logger.

Each logger to be used must implements 
[Psr\Log\LoggerInterface](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#3-psrlogloggerinterface).
 
## Installing
```
composer require opxcore/log-manager
```
## Basic creating:
```php
use OpxCore\Log\LogManager;

$manager = new LogManager($config);
```

## Creating with [container](https://github.com/opxcore/container)
```php
use OpxCore\Interfaces\LoggerInterface;
use OpxCore\Log\LogManager;

$container->bind(
    LoggerInterface::class,
    LogManager::class,
    ['config' => $config],
);

$manager = $container->make(LoggerInterface::class);
```
or
```php
$container->bind(LoggerInterface::class, LogManager::class);

$manager = $container->make(LoggerInterface::class, ['config' => $config]);
```
Where $config is configuration for log manager (see below).

## Configuring and using
Configuration array consists of two keys. Value of `'default'` must contain name 
of logger to be used as default logger. `'loggers'` is a set of loggers to be used
keyed by name. Required parameter of each logger is a `'driver'` containing class
name of logger to be used with corresponding name (See examples below).

Log manager extends [container](https://github.com/opxcore/container), so loggers
will be resolved by it with all dependency injections. All loggers will be resolved 
on demand and instanced for future use. All parameters except `'driver'` will be 
passed to logger constructor as parameters. 

Additionally you can bind custom created logger:
```php
$manager->registerLogger('custom_logger', function() {
    return new Logger(...);
});
```
  
To get a log driver call method 
`$manager->driver($name)` where `$name` is name of logger to be returned. If `null` 
given driver set as default will be used.

To get multiple log drivers use same method with array of names 
`$manager->driver([$name1, $name2])`

In both cases `LoggerProxy` class with chosen loggers bindings will be returned,
so `$manager->driver([$name1, $name2])->log($message)` will call log action on
each of logger.

## PSR-3
Log manager implements PSR-3. So available methods are:
```php
$manager->emergency($message, $context);
$manager->alert($message, $context);
$manager->critical($message, $context);
$manager->error($message, $context);
$manager->warning($message, $context);
$manager->notice($message, $context);
$manager->info($message, $context);
$manager->debug($message, $context);
$manager->log($level, $message, $context);
```
These methods will call corresponding method of log driver set as default.

## Examples
Log manager configuration:
```php
$config = [
    'default' => 'file',    
    'loggers' => [
        'file' => [
            'driver' => \OpxCore\Log\LogFile::class,
            'filename' => '/www/project/logs',
        ],
        'null' => [
            'driver' => \OpxCore\Log\LogNull::class,
        ]
    ],
];
```
Logger class:
```php
namespace \OpxCore\Log;

class LogFile implements \Psr\Log\LoggerInterface
{
    protected $filename;
    
    public function __create($filename)
    {
        $this->filename = $filename;
    }
    
    ...
}
```
Calling `$manager->driver('file')` at first time will create and return LogFile class 
instance (for this example is equal to `new \OpxCore\Log\LogFile('/www/project/logs')`) 
and store it's instance for future use. So calling `$manager->driver('file')` for 
second time will return same instance of logger.

For this example using
```php
$manager->driver('file')->debug('Some message');
```
is equal to (as `'file'` set as default driver)
```php
$manager->driver()->debug('Some message');
```
and equal to
```php
$manager->debug('Some message');
```

