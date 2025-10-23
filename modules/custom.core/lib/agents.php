<?php

declare(strict_types=1);

namespace Custom\Core;

class Agents
{
	public static function cleanupFileSemaphoresAgent(): string
	{
		try {
			$semaphoreManager = new FileSemaphoreManager();
			$semaphoreManager->cleanup();
		} catch (\Exception $e) {
			$logFile = $_SERVER['DOCUMENT_ROOT'] . '/semaphore_cleanup.log';
			$message = "[" . date('Y-m-d H:i:s') . "] Ошибка очистки семафоров: " . $e->getMessage() . PHP_EOL;
			file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
		}
		
		return "\\Custom\\Core\\Agents::cleanupFileSemaphoresAgent();";
	}
}