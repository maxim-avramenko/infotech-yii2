InfoTech Yii2 Book Store
===
.env файл собирается из переменных окружения в github.

SMS: SMSPILOT (`https://smspilot.ru/api.php`). Ключ задаётся секретом `SMS_API_KEY`.
`SMS_TEST=1` эмулирует отправку без передачи оператору. Callback URL (`SMS_CALLBACK_URL`) можно указать для вебхуков статуса доставки.

create admin:
```bash
docker compose exec php php yii user/create-admin admin@bookstore.local '+7 (900) 111-22-33' 'SuperStr0ngPaSSword'
```