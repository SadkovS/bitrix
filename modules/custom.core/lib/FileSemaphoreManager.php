<?php
namespace Custom\Core;

class FileSemaphoreManager
{
	protected ?string $semaphoreDir;
	protected int $ttlSeconds;
	
	/**
	 * Конструктор.
	 * @param string|null $semaphoreDir Путь к директории семафоров. Если null — будет использована /upload/semaphores
	 * @param int $ttlSeconds Время жизни семафора в секундах (для очистки)
	 */
	public function __construct(string $semaphoreDir = null, int $ttlSeconds = 3600)
	{
		$this->semaphoreDir = $semaphoreDir ?? $_SERVER['DOCUMENT_ROOT'] . '/upload/semaphores';
		$this->ttlSeconds = $ttlSeconds;
		
		if (!is_dir($this->semaphoreDir)) {
			mkdir($this->semaphoreDir, 0755, true);
		}
	}
	
	/**
	 * Захватить семафор по коду билета.
	 * Возвращает ресурс файла, который нужно держать открытым до освобождения.
	 * @param string $ticketCode
	 * @return resource
	 * @throws Exception
	 */
	public function acquire(string $ticketCode)
	{
		$filePath = $this->getFilePath($ticketCode);
		
		$fp = fopen($filePath, 'c+');
		if (!$fp) {
			throw new \Exception("Не удалось открыть файл семафора: {$filePath}");
		}
		
		if (!flock($fp, LOCK_EX)) {
			fclose($fp);
			throw new \Exception("Не удалось захватить файловый семафор: {$filePath}");
		}
		
		// Обновляем время модификации файла
		touch($filePath);
		
		return $fp;
	}
	
	/**
	 * Освободить семафор.
	 * @param resource $fp
	 * @return void
	 */
	public function release($fp): void
	{
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	
	/**
	 * Очистить старые файлы семафоров.
	 * Удаляет файлы, которые не изменялись дольше TTL.
	 * @return void
	 */
	public function cleanup(): void
	{
		$files = glob($this->semaphoreDir . '/*.lock');
		$now = time();
		
		foreach ($files as $file) {
			$lastModified = filemtime($file);
			if ($lastModified === false) {
				continue;
			}
			
			if (($now - $lastModified) > $this->ttlSeconds) {
				@unlink($file);
			}
		}
	}
	
	/**
	 * Получить полный путь к файлу семафора по коду билета.
	 * @param string $ticketCode
	 * @return string
	 */
	protected function getFilePath(string $ticketCode): string
	{
		return $this->semaphoreDir . '/' . md5($ticketCode) . '.lock';
	}
}

