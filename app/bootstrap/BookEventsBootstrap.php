<?php

declare(strict_types=1);

namespace app\bootstrap;

use app\events\BookCreatedEvent;
use app\handlers\BookCreatedSmsHandler;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;

class BookEventsBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!$app instanceof Application) {
            return;
        }

        $app->on(BookCreatedEvent::NAME, static function (Event $event): void {
            Yii::createObject(BookCreatedSmsHandler::class)->handle($event);
        });
    }
}
