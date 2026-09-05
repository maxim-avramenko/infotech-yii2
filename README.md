# InfoTech Yii2 Book Store

## Реализовано

- каталог книг
- несколько авторов у книги
- CRUD книг и авторов
- авторизация
- guest subscription
- TOP-10 authors by year
- SMS notification через SMSPilot
- Redis queue
- migrations
- tests
- .env файл собирается из переменных окружения в github.

## Архитектура

Controller → Service → Repository → ActiveRecord

BookCreatedEvent → Queue → SMS notification worker

## SMS
SMSPILOT (`https://smspilot.ru/api.php`). Ключ задаётся секретом `SMS_API_KEY`.
`SMS_TEST=1` эмулирует отправку без передачи оператору. Callback URL (`SMS_CALLBACK_URL`) можно указать для вебхуков статуса доставки.
