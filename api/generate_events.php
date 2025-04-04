<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Добавляем логирование в файл
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Логируем запуск скрипта
error_log('Starting generate_events.php script at ' . date('Y-m-d H:i:s'));

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

include $config_file;

if (!isset($OPENAI_API_KEY) || empty($OPENAI_API_KEY)) {
    error_log('API key not set in config file');
    die('API ключ не настроен в файле конфигурации');
}

$api_key = $OPENAI_API_KEY;

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
    
    error_log('Sending request to OpenAI API');
    
    // Инициализируем cURL
    $ch = curl_init($url);
    
    // Устанавливаем параметры cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    // Добавляем отладку cURL
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    try {
        // Выполняем запрос
        $result = curl_exec($ch);
        
        // Получаем отладочную информацию cURL
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        error_log('cURL verbose log: ' . $verboseLog);
        
        // Проверяем на ошибки cURL
        if ($result === FALSE) {
            $error = curl_error($ch);
            error_log('cURL error: ' . $error);
            throw new Exception('Ошибка при запросе к ChatGPT API: ' . $error);
        }
        
        // Получаем HTTP код ответа
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            error_log('HTTP error: ' . $httpCode . ', Response: ' . $result);
            throw new Exception('Ошибка HTTP при запросе к ChatGPT API: ' . $httpCode);
        }
        
        error_log('Received response from OpenAI API: ' . substr($result, 0, 100) . '...');
        
        $response = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            throw new Exception('Ошибка при разборе ответа от ChatGPT API: ' . json_last_error_msg());
        }
        
        if (isset($response['error'])) {
            error_log('OpenAI API error: ' . print_r($response['error'], true));
            $errorMessage = isset($response['error']['message']) ? $response['error']['message'] : 'Неизвестная ошибка от ChatGPT API';
            throw new Exception($errorMessage);
        }
        
        if (!isset($response['choices'][0]['message']['content'])) {
            error_log('Unexpected response format: ' . print_r($response, true));
            throw new Exception('Неожиданный формат ответа от ChatGPT API');
        }
        
        $csvText = $response['choices'][0]['message']['content'];
        error_log('CSV text: ' . $csvText);
        
        return $csvText;
    } catch (Exception $e) {
        error_log('Exception in getEventsFromChatGPT: ' . $e->getMessage());
        // Закрываем cURL сессию
        curl_close($ch);
        throw $e;
    }
}

// Функция для сохранения CSV в файл
function saveCSVToFile($csvText, $filename) {
    if (file_put_contents($filename, $csvText) === false) {
        error_log('Failed to save CSV to file: ' . $filename);
        throw new Exception('Не удалось сохранить CSV в файл');
    }
    error_log('CSV saved to file: ' . $filename);
    return true;
}

// Основной код
try {
    // Получаем CSV от ChatGPT
    $csvText = getEventsFromChatGPT($api_key);
    
    // Сохраняем CSV в файл
    $csvFile = __DIR__ . '/../data/events.csv';
    
    // Создаем директорию, если она не существует
    if (!file_exists(__DIR__ . '/../data')) {
        mkdir(__DIR__ . '/../data', 0755, true);
    }
    
    saveCSVToFile($csvText, $csvFile);
    
    // Создаем файл с датой последнего обновления
    $lastUpdateFile = __DIR__ . '/../data/last_update.txt';
    file_put_contents($lastUpdateFile, date('Y-m-d H:i:s'));
    
    echo "События успешно сгенерированы и сохранены в файл $csvFile\n";
} catch (Exception $e) {
    error_log('Error in generate_events.php: ' . $e->getMessage());
    echo "Ошибка: " . $e->getMessage() . "\n";
} 