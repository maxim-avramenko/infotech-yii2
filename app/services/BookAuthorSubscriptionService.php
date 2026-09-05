<?php

declare(strict_types=1);

namespace app\services;

use app\models\BookAuthorSubscription;
use app\repositories\BookAuthorRepository;
use app\repositories\BookAuthorSubscriptionRepository;
use app\repositories\BookRepository;
use InvalidArgumentException;

class BookAuthorSubscriptionService
{
    public function __construct(
        private readonly BookAuthorSubscriptionRepository $subscriptions,
        private readonly BookAuthorRepository $authors,
        private readonly BookRepository $books,
        private readonly SmsNotificationService $smsNotifications,
    ) {
    }

    public function subscribe(int $authorId, string $phone): BookAuthorSubscription
    {
        $phone = $this->normalizePhone($phone);
        $author = $this->authors->findByIdIncludingDeleted($authorId);
        if ($author === null) {
            throw new InvalidArgumentException('Author is required.');
        }

        $existing = $this->subscriptions->findByAuthorAndPhone($author->id, $phone);
        if ($existing !== null) {
            return $existing;
        }

        $subscription = new BookAuthorSubscription();
        $subscription->book_author_id = $author->id;
        $subscription->phone = $phone;
        $this->subscriptions->save($subscription);

        return $subscription;
    }

    public function createSmsNotificationsForBook(int $bookId): void
    {
        $book = $this->books->findById($bookId);
        if ($book === null) {
            return;
        }

        $phones = $this->subscriptions->findDistinctPhonesByAuthorIds($book->getAuthorIds());
        if ($phones === []) {
            return;
        }

        $text = sprintf('New book: %s (%d).', $book->title, $book->year);
        $messages = [];
        foreach ($phones as $phone) {
            $messages[] = [
                'phone' => $phone,
                'text' => $text,
            ];
        }

        $this->smsNotifications->enqueueForBook($bookId, $messages);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            throw new InvalidArgumentException('Phone must contain 10 to 15 digits.');
        }

        return $digits;
    }
}
