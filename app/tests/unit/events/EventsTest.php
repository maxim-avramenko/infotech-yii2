<?php

declare(strict_types=1);

namespace tests\unit\events;

use app\events\BookCreatedEvent;
use app\events\UserLoggedInEvent;

class EventsTest extends \Codeception\Test\Unit
{
    public function testEventPayloads(): void
    {
        $book = new BookCreatedEvent(8);
        verify($book->bookId)->equals(8);
        verify(BookCreatedEvent::NAME)->equals('bookCreated');

        $login = new UserLoggedInEvent(1, 'a@b.c', 'now', 'ip', 'Chrome');
        verify($login->userId)->equals(1);
        verify($login->email)->equals('a@b.c');
        verify($login->loggedInAt)->equals('now');
        verify($login->ip)->equals('ip');
        verify($login->browser)->equals('Chrome');
        verify(UserLoggedInEvent::NAME)->equals('userLoggedIn');
    }
}
