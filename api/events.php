<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Добавляем логирование в файл
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Логируем все запросы
error_log('Request received: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Отладочная информация
$debug_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI']
];

// Логируем отладочную информацию
error_log('Debug info: ' . print_r($debug_info, true));

// Функция для чтения событий из CSV-файла
function getEventsFromCSV() {
    $csvFile = __DIR__ . '/../data/events.csv';
    $lastUpdateFile = __DIR__ . '/../data/last_update.txt';
    
    // Проверяем существование файла
    if (!file_exists($csvFile)) {
        error_log('CSV file not found: ' . $csvFile);
        throw new Exception('Файл с событиями не найден. Пожалуйста, запустите скрипт генерации событий.');
    }
    
    // Читаем содержимое файла
    $csvContent = file_get_contents($csvFile);
    if ($csvContent === false) {
        error_log('Failed to read CSV file: ' . $csvFile);
        throw new Exception('Не удалось прочитать файл с событиями');
    }
    
    // Разбиваем CSV на строки
    $lines = array_filter(explode("\n", trim($csvContent)));
    
    // Проверяем, что файл не пустой
    if (count($lines) < 2) {
        error_log('CSV file is empty or has only headers: ' . $csvFile);
        throw new Exception('Файл с событиями пуст или содержит только заголовки');
    }
    
    // Пропускаем заголовок
    $events = [];
    for ($i = 1; $i < count($lines); $i++) {
        $line = $lines[$i];
        $fields = str_getcsv($line);
        
        if (count($fields) >= 4) {
            $events[] = [
                'name' => $fields[0],
                'date' => $fields[1],
                'category' => $fields[2],
                'description' => $fields[3]
            ];
        }
    }
    
    // Получаем дату последнего обновления
    $lastUpdate = file_exists($lastUpdateFile) ? file_get_contents($lastUpdateFile) : 'Неизвестно';
    
    return [
        'events' => $events,
        'lastUpdate' => $lastUpdate
    ];
}

// Обработка запроса
try {
    // Получаем события из CSV-файла
    $result = getEventsFromCSV();
    
    // Возвращаем успешный ответ
    echo json_encode([
        'success' => true,
        'events' => $result['events'],
        'lastUpdate' => $result['lastUpdate']
    ]);
} catch (Exception $e) {
    // Логируем ошибку
    error_log('Error in getEventsFromCSV: ' . $e->getMessage());
    
    // Возвращаем ошибку
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug_info
    ]);
} 