<?php

namespace app\components;

use app\jobs\SendEmailJob;
use app\jobs\SendSmsJob;
use Yii;
use yii\base\Component;
use yii\di\Instance;
use yii\queue\Queue;

class MessageBroker extends Component
{
    public Queue|string $emailQueue = 'queueEmail';
    public Queue|string $smsQueue = 'queueSms';

    public function init(): void
    {
        parent::init();
        $this->emailQueue = Instance::ensure($this->emailQueue, Queue::class);
        $this->smsQueue = Instance::ensure($this->smsQueue, Queue::class);
    }

    public function email(string $to, string $subject, string $textBody, ?string $htmlBody = null): string|int|null
    {
        return $this->emailQueue->push(Yii::createObject([
            'class' => SendEmailJob::class,
            'to' => $to,
            'subject' => $subject,
            'textBody' => $textBody,
            'htmlBody' => $htmlBody ?? $textBody,
        ]));
    }

    public function sms(string $phone, string $text): string|int|null
    {
        return $this->smsQueue->push(Yii::createObject([
            'class' => SendSmsJob::class,
            'phone' => $phone,
            'text' => $text,
        ]));
    }
}
