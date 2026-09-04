<?php

namespace app\commands;

use app\components\MessageBroker;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;

class NotifyController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly MessageBroker $messageBroker,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionEmail(string $to, string $subject, string $body): int
    {
        $id = $this->messageBroker->email($to, $subject, $body);
        $this->stdout("Email queued as #{$id}\n");

        return ExitCode::OK;
    }

    public function actionSms(string $phone, string $text): int
    {
        $id = $this->messageBroker->sms($phone, $text);
        $this->stdout("SMS queued as #{$id}\n");

        return ExitCode::OK;
    }
}
