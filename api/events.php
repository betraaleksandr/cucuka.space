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

// Получаем API ключ из переменной окружения или файла конфигурации
$api_key = null;
$config_file = __DIR__ . '/../config.php';

// Проверяем существование файла конфигурации
if (!file_exists($config_file)) {
    error_log('Config file not found: ' . $config_file);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Файл конфигурации не найден',
        'debug' => [
            'config_file_path' => $config_file,
            'file_exists' => false
        ]
    ]);
    exit;
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
    'api_key_set' => !empty($api_key),
    'api_key_length' => $api_key ? strlen($api_key) : 0,
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI']
];

// Логируем отладочную информацию
error_log('Debug info: ' . print_r($debug_info, true));

// Функция для запроса к ChatGPT API
function getEventsFromChatGPT($api_key) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => 'gpt-4-turbo-preview',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Ты - помощник по поиску событий в Санкт-Петербурге. Твоя задача - найти 2 интересных мероприятия в апреле 2025 года и вернуть их в формате CSV. ВАЖНО: верни ТОЛЬКО CSV текст, без дополнительного текста. Первая строка должна содержать заголовки: название,дата,категория,описание. Каждая следующая строка должна содержать данные события, разделенные запятыми. Если в поле есть запятые, заключи его в кавычки.'
            ],
            [
                'role' => 'user',
                'content' => 'Найди 2 интересных мероприятия в Санкт-Петербурге в апреле 2025 года и верни их в CSV формате.'
            ]
        ],
        'temperature' => 0.7
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\nAuthorization: Bearer $api_key\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    error_log('Sending request to OpenAI API');
    $context = stream_context_create($options);
    
    try {
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            $error = error_get_last();
            error_log('Error in file_get_contents: ' . print_r($error, true));
            throw new Exception('Ошибка при запросе к ChatGPT API: ' . ($error['message'] ?? 'Неизвестная ошибка'));
        }
        
        error_log('Received response from OpenAI API: ' . substr($result, 0, 100) . '...');
        
        $response = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            throw new Exception('Ошибка при разборе ответа от ChatGPT API: ' . json_last_error_msg());
        }
        
        if (isset($response['error'])) {
            error_log('OpenAI API error: ' . print_r($response['error'], true));
            throw new Exception($response['error']['message'] ?? 'Неизвестная ошибка от ChatGPT API');
        }
        
        if (!isset($response['choices'][0]['message']['content'])) {
            error_log('Unexpected response format: ' . print_r($response, true));
            throw new Exception('Неожиданный формат ответа от ChatGPT API');
        }
        
        $csvText = $response['choices'][0]['message']['content'];
        error_log('CSV text: ' . $csvText);
        
        // Разбиваем CSV на строки
        $lines = array_filter(explode("\n", trim($csvText)));
        
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
        
        return $events;
    } catch (Exception $e) {
        error_log('Exception in getEventsFromChatGPT: ' . $e->getMessage());
        throw $e;
    }
}

// Обработка запроса
try {
    // Получаем события от ChatGPT
    $events = getEventsFromChatGPT($api_key);
    
    // Возвращаем успешный ответ
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
} catch (Exception $e) {
    // Логируем ошибку
    error_log('Error in getEventsFromChatGPT: ' . $e->getMessage());
    
    // Возвращаем ошибку
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug_info
    ]);
} 