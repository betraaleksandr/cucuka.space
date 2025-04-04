<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Добавляем логирование в файл
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../api/error.log');

// Логируем запрос
error_log('Request to generate_events.php: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Метод не разрешен. Используйте POST.'
    ]);
    exit;
}

// Проверяем наличие API ключа в запросе
$apiKey = $_POST['api_key'] ?? '';
if (empty($apiKey)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'API ключ не указан'
    ]);
    exit;
}

// Запускаем скрипт генерации событий
$output = [];
$returnVar = 0;
$command = 'php ' . __DIR__ . '/../api/generate_events.php 2>&1';
exec($command, $output, $returnVar);

// Формируем ответ
$response = [
    'success' => $returnVar === 0,
    'output' => implode("\n", $output),
    'returnCode' => $returnVar
];

// Если произошла ошибка, устанавливаем код ответа 500
if ($returnVar !== 0) {
    http_response_code(500);
}

// Возвращаем ответ в формате JSON
header('Content-Type: application/json');
echo json_encode($response); 