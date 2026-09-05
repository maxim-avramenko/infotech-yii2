<?php

declare(strict_types=1);

namespace tests\unit\commands;

use app\commands\HelloController;
use app\commands\NotifyController;
use app\commands\SmsNotificationController;
use app\commands\UserController;
use app\components\MessageBroker;
use app\models\User;
use app\services\SmsNotificationService;
use app\services\UserService;
use tests\unit\DbTestCase;
use Yii;
use yii\console\ExitCode;

class CommandsTest extends DbTestCase
{
    public function testHelloEchoesMessage(): void
    {
        $controller = new HelloController('hello', Yii::$app);
        ob_start();
        $code = $controller->actionIndex('ping');
        $out = ob_get_clean();
        verify($code)->equals(ExitCode::OK);
        verify($out)->stringContainsString('ping');
    }

    public function testNotifyQueuesEmailAndSms(): void
    {
        $broker = $this->createMock(MessageBroker::class);
        $broker->expects($this->once())->method('email')->willReturn(1);
        $broker->expects($this->once())->method('sms')->willReturn(2);
        $controller = new NotifyController('notify', Yii::$app, $broker);

        verify($controller->actionEmail('a@b.c', 's', 'b'))->equals(ExitCode::OK);
        verify($controller->actionSms('7900', 'hi'))->equals(ExitCode::OK);
    }

    public function testUserCreateAdmin(): void
    {
        $users = $this->createMock(UserService::class);
        $user = new User();
        $user->id = 9;
        $user->email = 'a@b.c';
        $user->phone = '7900';
        $users->method('createAdmin')->willReturn($user);
        $controller = new UserController('user', Yii::$app, $users);
        verify($controller->actionCreateAdmin('a@b.c', '7900', 'password123'))->equals(ExitCode::OK);

        $users = $this->createMock(UserService::class);
        $users->method('createAdmin')->willThrowException(new \RuntimeException('nope'));
        $controller = new UserController('user', Yii::$app, $users);
        verify($controller->actionCreateAdmin('a@b.c', '7900', 'password123'))->equals(ExitCode::UNSPECIFIED_ERROR);
    }

    public function testSmsNotificationSendRunsLimitedCycles(): void
    {
        $sms = $this->createMock(SmsNotificationService::class);
        $sms->expects($this->exactly(2))->method('sendDue')->with(5)->willReturn(0);
        $controller = new SmsNotificationController('sms-notification', Yii::$app, $sms);
        verify($controller->actionSend(5, 1, 2))->equals(ExitCode::OK);
    }
}
