<?php

$db = require __DIR__ . '/db.php';
$db['dsn'] = sprintf(
    'mysql:host=%s;port=%s;dbname=%s',
    env('DB_HOST', 'mysql'),
    env('DB_PORT', '3306'),
    env('TEST_DB_NAME', 'book_store_test'),
);

return $db;
