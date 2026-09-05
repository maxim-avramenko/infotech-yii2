<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\User;
use app\models\UserRole;
use tests\unit\DbTestCase;

class UserTest extends DbTestCase
{
    public function testIdentityAndPassword(): void
    {
        $user = $this->createUser([
            'email' => 'admin@example.com',
            'phone' => '79001112233',
            'password' => 'adminpass',
            'auth_key' => 'test-key',
            'role' => UserRole::Administrator->value,
        ]);

        verify(User::findIdentity($user->id)?->email)->equals('admin@example.com');
        verify(User::findIdentity(999999))->null();
        verify(User::findIdentityByAccessToken('any'))->null();
        verify(User::findByEmail('admin@example.com')?->id)->equals($user->id);
        verify($user->getId())->equals((int) $user->id);
        verify($user->getAuthKey())->equals('test-key');
        verify($user->validateAuthKey('test-key'))->true();
        verify($user->validateAuthKey('other'))->false();
        verify($user->validatePassword('adminpass'))->true();
        verify($user->validatePassword('wrong'))->false();
        verify($user->getUserRole())->equals(UserRole::Administrator);
        verify($user->isAdministrator())->true();
        verify($this->createUser(['role' => UserRole::User->value])->isAdministrator())->false();
        verify($user->tableName())->equals('{{%user}}');
    }

    public function testRulesRequireUniqueEmail(): void
    {
        $this->createUser(['email' => 'dup@example.com', 'phone' => '79000000001']);
        $copy = new User([
            'email' => 'dup@example.com',
            'phone' => '79000000002',
            'password_hash' => 'x',
            'auth_key' => 'k',
            'role' => UserRole::User->value,
        ]);
        verify($copy->validate())->false();
        verify($copy->errors)->arrayHasKey('email');
    }
}
