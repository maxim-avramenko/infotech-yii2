<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        env('DB_HOST', 'mysql'),
        env('DB_PORT', '3306'),
        env('DB_NAME', 'book_store')
    ),
    'username' => env('DB_USER', 'infotech'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'enableSchemaCache' => YII_ENV_PROD,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
