<?php

declare(strict_types=1);

namespace tests\unit\handlers;

use app\components\MessageBroker;
use app\events\BookCreatedEvent;
use app\handlers\BookCreatedSmsHandler;
use yii\base\Event;

class BookCreatedSmsHandlerTest extends \Codeception\Test\Unit
{
    public function testHandleIgnoresUnknownEvents(): void
    {
        $broker = $this->createMock(MessageBroker::class);
        $broker->expects($this->never())->method('enqueueBookSubscriberSms');
        (new BookCreatedSmsHandler($broker))->handle(new Event());
    }

    public function testHandleEnqueuesSmsForBook(): void
    {
        $broker = $this->createMock(MessageBroker::class);
        $broker->expects($this->once())->method('enqueueBookSubscriberSms')->with(42);
        (new BookCreatedSmsHandler($broker))->handle(new BookCreatedEvent(42));
    }
}
