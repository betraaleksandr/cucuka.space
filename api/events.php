<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Добавляем логирование в файл
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Получаем API ключ из переменной окружения или файла конфигурации
$api_key = null;
$config_file = __DIR__ . '/../config.php';

if (file_exists($config_file)) {
    include $config_file;
    if (isset($OPENAI_API_KEY)) {
        $api_key = $OPENAI_API_KEY;
    }
}

if (!$api_key) {
    $api_key = getenv('OPENAI_API_KEY');
}

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

if (!$api_key) {
    $error_message = 'API ключ не настроен';
    error_log($error_message . '. Debug info: ' . print_r($debug_info, true));
    http_response_code(500);
    echo json_encode([
        'error' => $error_message,
        'debug' => $debug_info
    ]);
    exit;
}

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
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        throw new Exception('Ошибка при запросе к ChatGPT API');
    }
    
    $response = json_decode($result, true);
    
    if (isset($response['error'])) {
        throw new Exception($response['error']['message']);
    }
    
    $csvText = $response['choices'][0]['message']['content'];
    
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