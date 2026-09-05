<?php

declare(strict_types=1);

namespace app\handlers;

use app\components\MessageBroker;
use app\events\BookCreatedEvent;
use yii\base\Event;

class BookCreatedSmsHandler
{
    public function __construct(
        private readonly MessageBroker $messageBroker,
    ) {
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof BookCreatedEvent) {
            return;
        }

        $this->messageBroker->enqueueBookSubscriberSms($event->bookId);
    }
}
