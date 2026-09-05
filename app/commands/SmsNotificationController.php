<?php

declare(strict_types=1);

namespace app\commands;

use app\services\SmsNotificationService;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;

class SmsNotificationController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly SmsNotificationService $smsNotifications,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionSend(int $limit = 100, int $sleep = 2, int $cycles = 0): int
    {
        $this->stdout("Sending pending SMS notifications...\n");
        $n = 0;
        while ($cycles === 0 || $n < $cycles) {
            $sent = $this->smsNotifications->sendDue($limit);
            $n++;
            if ($sent === 0 && $cycles === 0) {
                sleep(max(1, $sleep));
            }
        }

        return ExitCode::OK;
    }
}
