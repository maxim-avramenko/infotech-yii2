<?php

declare(strict_types=1);

namespace app\services;

use app\components\SmsSender;
use app\exceptions\SmsSendException;
use app\models\SmsNotification;
use app\repositories\SmsNotificationRepository;
use Yii;

class SmsNotificationService
{
    public function __construct(
        private readonly SmsNotificationRepository $notifications,
        private readonly SmsSender $smsSender,
    ) {
    }

    public function enqueue(string $phone, string $text, ?int $bookId = null): SmsNotification
    {
        return $this->notifications->insertPendingOne($phone, $text, $bookId);
    }

    /**
     * @param list<array{phone: string, text: string}> $messages
     */
    public function enqueueForBook(int $bookId, array $messages): void
    {
        $rows = [];
        foreach ($messages as $message) {
            $rows[] = [
                'book_id' => $bookId,
                'phone' => $message['phone'],
                'text' => $message['text'],
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->notifications->insertPendingBatch($chunk);
        }
    }

    public function sendDue(int $limit = 100): int
    {
        $sent = 0;
        foreach ($this->notifications->findDue($limit) as $notification) {
            try {
                $ok = $this->smsSender->send($notification->phone, $notification->text);
                if ($ok !== true) {
                    throw new SmsSendException('SMS sender returned false.');
                }
                $this->notifications->markSent($notification);
                $sent++;
            } catch (\Throwable $exception) {
                Yii::error($exception, __METHOD__);
                $this->notifications->postpone($notification, $exception->getMessage());
            }
        }

        return $sent;
    }
}
