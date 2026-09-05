<?php

declare(strict_types=1);

namespace tests\unit\bootstrap;

use app\bootstrap\BookEventsBootstrap;
use app\bootstrap\UserEventsBootstrap;
use app\events\BookCreatedEvent;
use app\events\UserLoggedInEvent;
use Yii;
use yii\base\Component;

class BootstrapTest extends \Codeception\Test\Unit
{
    public function testIgnoresNonApplication(): void
    {
        (new BookEventsBootstrap())->bootstrap(new Component());
        (new UserEventsBootstrap())->bootstrap(new Component());
        verify(true)->true();
    }

    public function testRegistersApplicationListeners(): void
    {
        (new BookEventsBootstrap())->bootstrap(Yii::$app);
        (new UserEventsBootstrap())->bootstrap(Yii::$app);
        verify(Yii::$app->hasEventHandlers(BookCreatedEvent::NAME))->true();
        verify(Yii::$app->hasEventHandlers(UserLoggedInEvent::NAME))->true();
    }
}
