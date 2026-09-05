<?php

declare(strict_types=1);

namespace app\events;

use yii\base\Event;

class BookCreatedEvent extends Event
{
    public const NAME = 'bookCreated';

    public function __construct(
        public readonly int $bookId,
        array $config = [],
    ) {
        parent::__construct($config);
    }
}
