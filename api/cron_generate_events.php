<?php
/**
 * Скрипт для запуска генерации событий по расписанию
 * 
 * Этот скрипт должен запускаться по расписанию (например, раз в неделю) через cron.
 * Пример настройки cron для запуска каждое воскресенье в 00:00:
 * 0 0 * * 0 php /path/to/cucuka.space/api/cron_generate_events.php
 */

// Логируем запуск скрипта
$logFile = __DIR__ . '/cron.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Starting cron_generate_events.php' . PHP_EOL, FILE_APPEND);

// Проверяем, нужно ли обновлять события
$lastUpdateFile = __DIR__ . '/../data/last_update.txt';
$shouldUpdate = true;

if (file_exists($lastUpdateFile)) {
    $lastUpdate = file_get_contents($lastUpdateFile);
    $lastUpdateTime = strtotime($lastUpdate);
    $currentTime = time();
    
    // Если последнее обновление было менее 6 дней назад, пропускаем
    if (($currentTime - $lastUpdateTime) < (6 * 24 * 60 * 60)) {
        $shouldUpdate = false;
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Skipping update, last update was less than 6 days ago' . PHP_EOL, FILE_APPEND);
    }
}

if ($shouldUpdate) {
    // Запускаем скрипт генерации событий
    $output = [];
    $returnVar = 0;
    exec('php ' . __DIR__ . '/generate_events.php 2>&1', $output, $returnVar);
    
    // Логируем результат
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Executed generate_events.php with return code: ' . $returnVar . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Output: ' . implode(PHP_EOL, $output) . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - No update needed' . PHP_EOL, FILE_APPEND);
}

file_put_contents($logFile, date('Y-m-d H:i:s') . ' - Finished cron_generate_events.php' . PHP_EOL, FILE_APPEND); 