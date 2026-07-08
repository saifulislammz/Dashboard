<?php

declare(strict_types=1);

namespace App\Utils;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/app.log';
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $date = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';
        $logEntry = sprintf("[%s] %s: %s %s" . PHP_EOL, $date, strtoupper($level), $message, $contextString);
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }
}
