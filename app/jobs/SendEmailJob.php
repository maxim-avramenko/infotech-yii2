<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

class SendEmailJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    public string $to;
    public string $subject;
    public string $textBody;
    public ?string $htmlBody = null;

    public function execute($queue): void
    {
        Yii::$app->mailer->compose()
            ->setTo($this->to)
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setSubject($this->subject)
            ->setTextBody($this->textBody)
            ->setHtmlBody($this->htmlBody ?? $this->textBody)
            ->send();
    }

    public function getTtr(): int
    {
        return 60;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }
}
