<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\components\SmsSender;
use app\models\BookAuthorSubscription;
use app\models\SmsNotification;
use app\repositories\BookAuthorRepository;
use app\repositories\BookAuthorSubscriptionRepository;
use app\repositories\BookRepository;
use app\repositories\SmsNotificationRepository;
use app\services\BookAuthorSubscriptionService;
use app\services\SmsNotificationService;
use InvalidArgumentException;
use tests\unit\DbTestCase;

class BookAuthorSubscriptionServiceTest extends DbTestCase
{
    private BookAuthorSubscriptionService $service;

    protected function _before(): void
    {
        parent::_before();
        $this->service = new BookAuthorSubscriptionService(
            new BookAuthorSubscriptionRepository(),
            new BookAuthorRepository(),
            new BookRepository(),
            new SmsNotificationService(new SmsNotificationRepository(), $this->createMock(SmsSender::class)),
        );
    }

    public function testSubscribeCreatesAndIsIdempotent(): void
    {
        $author = $this->createAuthor($this->createUser());
        $first = $this->service->subscribe((int) $author->id, '+7 (900) 123-45-67');
        $second = $this->service->subscribe((int) $author->id, '79001234567');

        verify($first)->instanceOf(BookAuthorSubscription::class);
        verify($first->phone)->equals('79001234567');
        verify($second->id)->equals($first->id);
    }

    public function testSubscribeRejectsUnknownAuthorAndShortPhone(): void
    {
        try {
            $this->service->subscribe(999, '79001234567');
            $this->fail('Expected author exception');
        } catch (InvalidArgumentException $exception) {
            verify($exception->getMessage())->equals('Author is required.');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->service->subscribe(1, '123');
    }

    public function testSubscribeRejectsDeletedAuthor(): void
    {
        $author = $this->createAuthor($this->createUser());
        $author->deleted_at = date('Y-m-d H:i:s');
        $author->save(false, ['deleted_at']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Author is required.');
        $this->service->subscribe((int) $author->id, '79001234567');
    }

    public function testCreateSmsNotificationsForBookEnqueuesDistinctPhones(): void
    {
        $user = $this->createUser();
        $first = $this->createAuthor($user, ['lastname' => 'One']);
        $second = $this->createAuthor($user, ['lastname' => 'Two']);
        $book = $this->createBook($user, [(int) $first->id, (int) $second->id], ['title' => 'New', 'year' => 2024]);
        $this->createSubscription((int) $first->id, '79001111111');
        $this->createSubscription((int) $second->id, '79001111111');
        $this->createSubscription((int) $second->id, '79002222222');

        $this->service->createSmsNotificationsForBook((int) $book->id);

        $rows = SmsNotification::find()->orderBy(['phone' => SORT_ASC])->all();
        verify(count($rows))->equals(2);
        verify($rows[0]->text)->equals('New book: New (2024).');
    }

    public function testCreateSmsNotificationsSkipsMissingBookAndEmptyPhones(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);

        $this->service->createSmsNotificationsForBook(999999);
        $this->service->createSmsNotificationsForBook((int) $book->id);

        verify(SmsNotification::find()->count())->equals(0);
    }
}
