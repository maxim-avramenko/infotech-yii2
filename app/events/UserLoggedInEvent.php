<?php

declare(strict_types=1);

namespace app\events;

use yii\base\Event;

class UserLoggedInEvent extends Event
{
    public const NAME = 'userLoggedIn';

    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $loggedInAt,
        public readonly string $ip,
        public readonly string $browser,
        array $config = [],
    ) {
        parent::__construct($config);
    }
}
