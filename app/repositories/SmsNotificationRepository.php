<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\SmsNotification;
use yii\db\Expression;
use yii\db\IntegrityException;

class SmsNotificationRepository
{
    /**
     * @param list<array{book_id: int, phone: string, text: string}> $rows
     */
    public function insertPendingBatch(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                $row['book_id'],
                $row['phone'],
                $row['text'],
                SmsNotification::STATUS_PENDING,
                0,
                null,
                new Expression('NOW()'),
                new Expression('NOW()'),
                null,
            ];
        }

        try {
            $command = SmsNotification::getDb()->createCommand()->batchInsert(
                SmsNotification::tableName(),
                ['book_id', 'phone', 'text', 'status', 'attempts', 'last_error', 'next_attempt_at', 'created_at', 'sent_at'],
                $payload,
            );
            $sql = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $command->getRawSql(), 1);
            if (!is_string($sql)) {
                throw new \RuntimeException('Unable to build SMS notification insert.');
            }
            SmsNotification::getDb()->createCommand($sql)->execute();
        } catch (IntegrityException) {
            foreach ($rows as $row) {
                $this->insertPendingOne($row['phone'], $row['text'], $row['book_id']);
            }
        }
    }

    public function insertPendingOne(string $phone, string $text, ?int $bookId = null): SmsNotification
    {
        $existing = $bookId === null
            ? null
            : SmsNotification::findOne(['book_id' => $bookId, 'phone' => $phone]);
        if ($existing !== null) {
            return $existing;
        }

        $notification = new SmsNotification();
        $notification->book_id = $bookId;
        $notification->phone = $phone;
        $notification->text = $text;
        $notification->status = SmsNotification::STATUS_PENDING;
        $notification->attempts = 0;
        $notification->next_attempt_at = new Expression('NOW()');
        try {
            if (!$notification->save(false)) {
                throw new \RuntimeException('Unable to save SMS notification.');
            }
        } catch (IntegrityException) {
            $existing = $bookId === null
                ? null
                : SmsNotification::findOne(['book_id' => $bookId, 'phone' => $phone]);
            if ($existing === null) {
                throw new \RuntimeException('Unable to save SMS notification.');
            }

            return $existing;
        }

        return $notification;
    }

    /**
     * @return SmsNotification[]
     */
    public function findDue(int $limit): array
    {
        return SmsNotification::find()
            ->andWhere(['status' => SmsNotification::STATUS_PENDING])
            ->andWhere(['<=', 'next_attempt_at', new Expression('NOW()')])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    public function markSent(SmsNotification $notification): void
    {
        $notification->status = SmsNotification::STATUS_SENT;
        $notification->sent_at = new Expression('NOW()');
        $notification->last_error = null;
        if ($notification->save(false, ['status', 'sent_at', 'last_error']) === false) {
            throw new \RuntimeException('Unable to mark SMS notification as sent.');
        }
    }

    public function postpone(SmsNotification $notification, string $error, int $delaySeconds = 60): void
    {
        $notification->attempts = (int) $notification->attempts + 1;
        $notification->last_error = mb_substr($error, 0, 255);
        $notification->next_attempt_at = new Expression('DATE_ADD(NOW(), INTERVAL ' . $delaySeconds . ' SECOND)');
        if ($notification->save(false, ['attempts', 'last_error', 'next_attempt_at']) === false) {
            throw new \RuntimeException('Unable to postpone SMS notification.');
        }
    }
}
