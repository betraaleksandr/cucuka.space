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

// Устанавливаем заголовки для JSON и CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Отладочная информация
$debug_info = [
    'php_version' => PHP_VERSION,
    'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI']
];

// Логируем отладочную информацию
error_log('Debug info: ' . print_r($debug_info, true));

// Функция для чтения событий из CSV-файла
function getEventsFromCSV() {
    $csvFile = __DIR__ . '/../data/events.csv';
    
    // Проверяем существование файла
    if (!file_exists($csvFile)) {
        error_log('CSV file not found: ' . $csvFile);
        throw new Exception('Файл с событиями не найден');
    }
    
    // Проверяем права доступа
    if (!is_readable($csvFile)) {
        error_log('CSV file is not readable: ' . $csvFile);
        throw new Exception('Нет доступа к файлу с событиями');
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
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        
        $fields = str_getcsv($line);
        
        if (count($fields) >= 4) {
            $events[] = [
                'name' => trim($fields[0]),
                'date' => trim($fields[1]),
                'category' => trim($fields[2]),
                'description' => trim($fields[3])
            ];
        }
    }
    
    // Получаем дату последнего обновления файла
    $lastUpdate = date('d.m.Y H:i:s', filemtime($csvFile));
    
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
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Логируем ошибку
    error_log('Error in getEventsFromCSV: ' . $e->getMessage());
    
    // Возвращаем ошибку
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 