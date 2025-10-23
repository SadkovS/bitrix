<?php

namespace Custom\Core;

class SimpleLogger
{
    private static string $baseDir = '';

    // Инициализация базовой папки логов
    public static function init(string $baseDir = null): void
    {
        self::$baseDir = $baseDir ?? $_SERVER['DOCUMENT_ROOT'] . '/logs';
        if (!is_dir(self::$baseDir)) {
            mkdir(self::$baseDir, 0755, true);
        }
    }

    /**
     * Логирование сообщения
     * @param string $message текст сообщения
     * @param string $type 'E' = error, 'I' = info
     * @param string $subFolder подпапка внутри logs
     * @param string $fileName имя файла
     */
    public static function log(string $message, string $type = 'I', string $subFolder = '', string $fileName = 'log'): void
    {
        if (!self::$baseDir) {
            self::init();
        }

        $type = strtoupper($type);
        $typeStr = $type === 'E' ? 'ERROR' : 'INFO';
        $suffix  = $type === 'E' ? '_error' : '_info';

        $folder = rtrim(self::$baseDir . '/' . $subFolder, '/');
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $dateStr = date('Y-m-d');
        $timeStr = date('Y-m-d H:i:s');

        $logFile = "{$folder}/{$dateStr}_{$fileName}_{$suffix}.log";

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? null;
        $callerInfo = $caller ? basename($caller['file']) . ':' . $caller['line'] : 'unknown';

        $logLine = "[{$timeStr}] [{$typeStr}] [{$callerInfo}] {$message}\n";

        error_log($logLine, 3, $logFile);
    }
}
