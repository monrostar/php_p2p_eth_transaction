<?php


namespace App;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    public static function init(string $name = 'default'): MonologLogger
    {
        // the default date format is "Y-m-d\TH:i:sP"
        $dateFormat = "Y.n.j, g:i a";
        // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        $output = "%datetime% > %level_name% > %message% %context% %extra%\n";
        // finally, create a formatter
        $formatter = new LineFormatter($output, $dateFormat);
        // Create a handler
        $stream = new StreamHandler(__DIR__ . '/../logs/app.log', MonologLogger::DEBUG);
        $stream->setFormatter($formatter);
        // bind it to a logger object
        $logger = new Logger($name);
        $logger->pushHandler($stream);
        $logger->pushHandler(new StreamHandler('php://stdout', MonologLogger::DEBUG));
        return $logger;
    }

    /**
     * Adds a log record.
     *
     * @param  int     $level   The logging level
     * @param  string  $message The log message
     * @param  mixed[] $context The log context
     * @return bool    Whether the record has been processed
     */
    public function addRecord(int $level, string $message, array $context = []): bool
    {
        echo "LOG: " . json_encode([
            'level' => static::getLevelName($level),
            'message' => $message,
            'context' => $context,
        ]) . PHP_EOL;
        return parent::addRecord($level, $message, $context);
    }
}
