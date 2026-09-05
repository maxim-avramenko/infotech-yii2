<?php

declare(strict_types=1);

namespace tests\unit\handlers;

use app\components\MessageBroker;
use app\events\UserLoggedInEvent;
use app\handlers\UserLoggedInEmailHandler;
use yii\base\Event;

class UserLoggedInEmailHandlerTest extends \Codeception\Test\Unit
{
    public function testHandleIgnoresUnknownEventsAndEmptyEmail(): void
    {
        $broker = $this->createMock(MessageBroker::class);
        $broker->expects($this->never())->method('email');
        $handler = new UserLoggedInEmailHandler($broker);
        $handler->handle(new Event());
        $handler->handle(new UserLoggedInEvent(1, '  ', 'now', 'ip', 'Chrome'));
    }

    public function testHandleSendsEmailWithEscapedHtml(): void
    {
        $broker = $this->createMock(MessageBroker::class);
        $broker->expects($this->once())->method('email')->with(
            'user@example.com',
            'Вход в аккаунт',
            $this->callback(static fn (string $text): bool => str_contains($text, 'Firefox') && str_contains($text, '<script>')),
            $this->callback(static fn (string $html): bool => str_contains($html, '&lt;script&gt;') && str_contains($html, 'Firefox')),
        );

        (new UserLoggedInEmailHandler($broker))->handle(new UserLoggedInEvent(
            1,
            '  user@example.com  ',
            '01.01.2026 12:00:00 UTC',
            '<script>',
            'Firefox',
        ));
    }
}
