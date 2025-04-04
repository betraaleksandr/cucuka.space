# cucuka.space

Официальный веб-сайт проекта cucuka.space

## О проекте

Минималистичный одностраничный сайт с приветственным сообщением.

## Технологии

- HTML5
- CSS3
- GitHub Actions для автоматического деплоя

## Установка

```bash
git clone https://github.com/betraaleksandr/cucuka.space.git
cd cucuka.space
```

## Настройка автоматического деплоя

1. Создайте FTP пользователя в панели ISPManager
2. Добавьте следующие секреты в настройках GitHub репозитория (Settings -> Secrets and variables -> Actions):
   - `FTP_SERVER`: адрес FTP сервера (например, ftp.cucuka.space)
   - `FTP_USERNAME`: имя пользователя FTP
   - `FTP_PASSWORD`: пароль FTP пользователя

После настройки, при каждом пуше в ветку `main` сайт будет автоматически обновляться через FTP.

## Настройка API ключа на сервере

Поскольку файл `config.php` добавлен в `.gitignore` для безопасности, он не загружается на сервер при деплое через GitHub Actions. Вам нужно создать этот файл на сервере вручную.

### Вариант 1: Создание файла через FTP

1. Подключитесь к серверу через FTP
2. Перейдите в директорию `/var/www/u3081833/data/www/cucuka.space/`
3. Создайте файл `config.php` со следующим содержимым:

```php
<?php
// Файл конфигурации для хранения API ключей
// ВАЖНО: Не добавляйте этот файл в Git репозиторий!

// API ключ OpenAI
// ВАЖНО: API ключ должен начинаться с 'sk-' и иметь правильный формат
$OPENAI_API_KEY = 'YOUR_API_KEY_HERE'; // Замените на ваш API ключ
```

4. Замените `YOUR_API_KEY_HERE` на ваш API ключ OpenAI

### Вариант 2: Создание файла через SSH

1. Подключитесь к серверу через SSH
2. Перейдите в директорию `/var/www/u3081833/data/www/cucuka.space/`
3. Создайте файл `config.php` с помощью команды:

```bash
echo '<?php
// Файл конфигурации для хранения API ключей
// ВАЖНО: Не добавляйте этот файл в Git репозиторий!

// API ключ OpenAI
// ВАЖНО: API ключ должен начинаться с "sk-" и иметь правильный формат
$OPENAI_API_KEY = "YOUR_API_KEY_HERE"; // Замените на ваш API ключ
' > config.php
```

4. Отредактируйте файл с помощью команды:

```bash
nano config.php
```

5. Замените `YOUR_API_KEY_HERE` на ваш API ключ OpenAI
6. Сохраните файл (Ctrl+O, затем Enter) и выйдите (Ctrl+X)

### Вариант 3: Использование скрипта create_config.php

1. Загрузите файл `scripts/create_config.php` на сервер через FTP
2. Подключитесь к серверу через SSH
3. Перейдите в директорию `/var/www/u3081833/data/www/cucuka.space/scripts/`
4. Запустите скрипт:

```bash
php create_config.php
```

5. Отредактируйте созданный файл `config.php` и замените `YOUR_API_KEY_HERE` на ваш API ключ OpenAI
6. Удалите скрипт `create_config.php` с сервера

## Проверка API ключа

После настройки API ключа, вы можете проверить его, открыв в браузере:

```
https://cucuka.space/api/test_api_key.php
```

Этот скрипт вернет JSON с информацией о состоянии API ключа. Если ключ недействителен, вы увидите сообщение об ошибке.

## Получение API ключа OpenAI

Для получения правильного API ключа:
1. Зайдите на сайт OpenAI: https://platform.openai.com/
2. Войдите в свой аккаунт
3. Перейдите в раздел "API keys"
4. Создайте новый ключ или используйте существующий
5. Скопируйте ключ и обновите его в файле `config.php`

## Локальная разработка

Для локальной разработки:

1. Клонируйте репозиторий
2. Установите зависимости: `npm install`
3. Создайте файл `config.php` в корневой директории проекта
4. Запустите локальный сервер: `php -S localhost:8000`
5. Откройте в браузере: `http://localhost:8000` 