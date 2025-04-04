<?php
// Скрипт для создания файла config.php на сервере
// ВАЖНО: Этот скрипт должен быть запущен только один раз на сервере
// После создания файла config.php, удалите этот скрипт

// Проверяем, существует ли уже файл config.php
$config_file = __DIR__ . '/../config.php';
if (file_exists($config_file)) {
    echo "Файл config.php уже существует. Удалите его, если хотите создать новый.\n";
    exit;
}

// Создаем содержимое файла config.php
$config_content = <<<EOT
<?php
// Файл конфигурации для хранения API ключей
// ВАЖНО: Не добавляйте этот файл в Git репозиторий!

// API ключ OpenAI
// ВАЖНО: API ключ должен начинаться с 'sk-' и иметь правильный формат
\$OPENAI_API_KEY = 'YOUR_API_KEY_HERE'; // Замените на ваш API ключ
EOT;

// Записываем содержимое в файл
if (file_put_contents($config_file, $config_content)) {
    echo "Файл config.php успешно создан.\n";
    echo "Теперь отредактируйте файл и замените 'YOUR_API_KEY_HERE' на ваш API ключ OpenAI.\n";
} else {
    echo "Ошибка при создании файла config.php.\n";
} 