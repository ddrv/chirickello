<?php

declare(strict_types=1);

namespace Chirickello\Package\LoggerFile;

use Chirickello\Package\Timer\TimerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerFile implements LoggerInterface
{
    private string $logFile;
    private string $service;
    private TimerInterface $timer;

    public function __construct(string $logFile, string $service, TimerInterface $timer)
    {
        $this->logFile = $logFile;
        $this->service = $service;
        $this->timer = $timer;
    }

    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $msg = $message;
        foreach ($context as $key=>$value) {
            $msg = str_replace('{' . $key . '}', $value, $msg);
        }
        $log = sprintf(
            '%s [%s] :%s: %s',
            $this->timer->now()->format('Y-m-d\TH:i:s'),
            $this->service,
            strtoupper($level),
            $msg
        );
        file_put_contents($this->logFile, $log . PHP_EOL, FILE_APPEND);
    }
}
