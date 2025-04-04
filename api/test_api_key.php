<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Добавляем логирование в файл
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Логируем все запросы
error_log('Test API key script started');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Получаем API ключ из файла конфигурации
$config_file = __DIR__ . '/../config.php';
// Добавляем отладочную информацию
error_log('Config file path: ' . $config_file);
error_log('Current directory: ' . __DIR__);
error_log('Document root: ' . $_SERVER['DOCUMENT_ROOT']);

// Пробуем несколько вариантов пути к файлу конфигурации
$config_paths = [
    __DIR__ . '/../config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/config.php',
    dirname(dirname(__FILE__)) . '/config.php'
];

$config_file = null;
foreach ($config_paths as $path) {
    error_log('Trying config path: ' . $path);
    if (file_exists($path)) {
        $config_file = $path;
        error_log('Config file found at: ' . $path);
        break;
    }
}

if (!$config_file) {
    error_log('Config file not found in any of the following paths: ' . implode(', ', $config_paths));
    die('Файл конфигурации не найден');
}

// Включаем файл конфигурации
include $config_file;

// Проверяем, установлен ли API ключ
if (!isset($OPENAI_API_KEY) || empty($OPENAI_API_KEY)) {
    error_log('API key not set in config file');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'API ключ не настроен в файле конфигурации',
        'debug' => [
            'config_file_path' => $config_file,
            'api_key_set' => isset($OPENAI_API_KEY),
            'api_key_length' => isset($OPENAI_API_KEY) ? strlen($OPENAI_API_KEY) : 0
        ]
    ]);
    exit;
}

$api_key = $OPENAI_API_KEY;

// Отладочная информация
$debug_info = [
    'config_file_exists' => file_exists($config_file),
    'config_file_path' => $config_file,
    'api_key_set' => isset($OPENAI_API_KEY),
    'api_key_length' => isset($api_key) ? strlen($api_key) : 0,
    'api_key_prefix' => isset($api_key) ? substr($api_key, 0, 10) . '...' : 'none',
    'php_version' => PHP_VERSION,
    'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI']
];

// Логируем отладочную информацию
error_log('Debug info: ' . print_r($debug_info, true));

// Проверяем формат API ключа
$api_key_format_valid = (strpos($api_key, 'sk-') === 0);
$api_key_length_valid = (strlen($api_key) > 20);

// Возвращаем результат проверки
echo json_encode([
    'success' => true,
    'api_key_valid' => $api_key_format_valid && $api_key_length_valid,
    'api_key_format_valid' => $api_key_format_valid,
    'api_key_length_valid' => $api_key_length_valid,
    'api_key_prefix' => isset($api_key) ? substr($api_key, 0, 10) . '...' : 'none',
    'api_key_length' => strlen($api_key),
    'debug' => $debug_info
]); 