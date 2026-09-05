<?php

declare(strict_types=1);

namespace app\bootstrap;

use app\events\UserLoggedInEvent;
use app\handlers\UserLoggedInEmailHandler;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;

class UserEventsBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$app instanceof Application) {
            return;
        }

        $app->on(UserLoggedInEvent::NAME, static function (Event $event): void {
            Yii::createObject(UserLoggedInEmailHandler::class)->handle($event);
        });
    }
}
