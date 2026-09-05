<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\LoginForm;

class LoginFormTest extends \Codeception\Test\Unit
{
    public function testValidationAndLabels(): void
    {
        $model = new LoginForm();
        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('login');
        verify($model->errors)->arrayHasKey('password');

        $model->login = 'user@example.com';
        $model->password = 'secret';
        $model->rememberMe = '1';
        verify($model->validate())->true();
        verify($model->attributeLabels()['login'])->equals('Email or phone');
    }
}
