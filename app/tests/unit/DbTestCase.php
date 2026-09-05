<?php

declare(strict_types=1);

namespace tests\unit;

use app\models\Book;
use app\models\BookAuthor;
use app\models\BookAuthorSubscription;
use app\models\SmsNotification;
use app\models\User;
use app\models\UserRole;
use Yii;
use yii\db\Expression;

abstract class DbTestCase extends \Codeception\Test\Unit
{
    private static bool $schemaReady = false;

    protected function _before(): void
    {
        $this->ensureSchema();
        $this->truncateAll();
    }

    protected function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        if (Yii::$app->db->getTableSchema('{{%user}}', true) === null) {
            $yii = dirname(__DIR__) . '/bin/yii';
            $cmd = PHP_BINARY . ' ' . escapeshellarg($yii) . ' migrate --interactive=0';
            exec($cmd, $output, $code);
            if ($code !== 0) {
                throw new \RuntimeException("Unable to migrate test database:\n" . implode("\n", $output));
            }
        }

        self::$schemaReady = true;
    }

    protected function truncateAll(): void
    {
        $db = Yii::$app->db;
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ([
            '{{%sms_notification}}',
            '{{%book_author_subscription}}',
            '{{%book_book_author}}',
            '{{%book}}',
            '{{%book_author}}',
            '{{%user}}',
        ] as $table) {
            if ($db->getTableSchema($table, true) !== null) {
                $db->createCommand()->truncateTable($table)->execute();
            }
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    protected function createUser(array $attrs = []): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User();
        $user->email = $attrs['email'] ?? "user{$suffix}@example.com";
        $user->phone = $attrs['phone'] ?? substr(preg_replace('/\D+/', '', '79' . random_int(100000000, 999999999)) ?? '79000000000', 0, 15);
        $user->password_hash = $attrs['password_hash'] ?? Yii::$app->security->generatePasswordHash($attrs['password'] ?? 'password123');
        $user->auth_key = $attrs['auth_key'] ?? Yii::$app->security->generateRandomString();
        $user->role = $attrs['role'] ?? UserRole::User->value;
        if (isset($attrs['email_verified_at'])) {
            $user->email_verified_at = $attrs['email_verified_at'];
        }
        if (isset($attrs['phone_verified_at'])) {
            $user->phone_verified_at = $attrs['phone_verified_at'];
        }
        if (!$user->save()) {
            throw new \RuntimeException(implode('; ', $user->getFirstErrors()));
        }

        return $user;
    }

    protected function createAuthor(User $createdBy, array $attrs = []): BookAuthor
    {
        $author = new BookAuthor();
        $author->lastname = $attrs['lastname'] ?? 'Doe';
        $author->firstname = $attrs['firstname'] ?? 'Jane';
        $author->secondname = array_key_exists('secondname', $attrs) ? $attrs['secondname'] : 'Ann';
        $author->created_by = $createdBy->id;
        if (array_key_exists('deleted_at', $attrs)) {
            $author->deleted_at = $attrs['deleted_at'];
        }
        if (!$author->save(false)) {
            throw new \RuntimeException('Unable to save author.');
        }

        return $author;
    }

    /**
     * @param int[] $authorIds
     */
    protected function createBook(User $createdBy, array $authorIds, array $attrs = []): Book
    {
        $book = new Book();
        $book->title = $attrs['title'] ?? 'Book title';
        $book->year = $attrs['year'] ?? 2024;
        $book->description = array_key_exists('description', $attrs) ? $attrs['description'] : 'Desc';
        $book->isbn = $attrs['isbn'] ?? ('isbn-' . bin2hex(random_bytes(4)));
        $book->image = $attrs['image'] ?? null;
        $book->created_by = $createdBy->id;
        if (array_key_exists('deleted_at', $attrs)) {
            $book->deleted_at = $attrs['deleted_at'];
        }
        if (!$book->save(false)) {
            throw new \RuntimeException('Unable to save book.');
        }

        foreach ($authorIds as $authorId) {
            Yii::$app->db->createCommand()->insert('{{%book_book_author}}', [
                'book_id' => $book->id,
                'book_author_id' => $authorId,
            ])->execute();
        }
        $book->refresh();

        return $book;
    }

    protected function createSubscription(int $authorId, string $phone): BookAuthorSubscription
    {
        $subscription = new BookAuthorSubscription();
        $subscription->book_author_id = $authorId;
        $subscription->phone = $phone;
        if (!$subscription->save(false)) {
            throw new \RuntimeException('Unable to save subscription.');
        }

        return $subscription;
    }

    protected function createSmsNotification(array $attrs = []): SmsNotification
    {
        $notification = new SmsNotification();
        $notification->book_id = $attrs['book_id'] ?? null;
        $notification->phone = $attrs['phone'] ?? '79001234567';
        $notification->text = $attrs['text'] ?? 'Hello';
        $notification->status = $attrs['status'] ?? SmsNotification::STATUS_PENDING;
        $notification->attempts = $attrs['attempts'] ?? 0;
        $notification->last_error = $attrs['last_error'] ?? null;
        $notification->next_attempt_at = $attrs['next_attempt_at'] ?? new Expression('NOW()');
        if (!$notification->save(false)) {
            throw new \RuntimeException('Unable to save SMS notification.');
        }

        return $notification;
    }
}
