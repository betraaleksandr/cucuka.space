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