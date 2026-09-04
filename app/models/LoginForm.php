<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

class LoginForm extends Model
{
    public string $login = '';
    public string $password = '';
    public $rememberMe = true;

    public function rules(): array
    {
        return [
            [['login', 'password'], 'required'],
            ['login', 'string', 'max' => 255],
            ['rememberMe', 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => 'Email or phone',
            'password' => 'Password',
            'rememberMe' => 'Remember me',
        ];
    }
}
