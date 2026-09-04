<?php

use yii\redis\Connection;

return [
    'class' => Connection::class,
    'hostname' => env('REDIS_HOST', 'redis'),
    'port' => (int) env('REDIS_PORT', 6379),
    'database' => (int) env('REDIS_DATABASE', 0),
];
