<?php

use yii\caching\MemCache;

return [
    'class' => MemCache::class,
    'useMemcached' => true,
    'servers' => [
        [
            'host' => env('MEMCACHED_HOST', 'memcached'),
            'port' => (int) env('MEMCACHED_PORT', 11211),
        ],
    ],
];
