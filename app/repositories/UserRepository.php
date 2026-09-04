<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\User;

class UserRepository
{
    public function findById(int $id): ?User
    {
        return User::findOne(['id' => $id]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::findOne(['email' => $email]);
    }

    public function findByPhone(string $phone): ?User
    {
        return User::findOne(['phone' => $phone]);
    }

    public function existsByEmail(string $email): bool
    {
        return User::find()->where(['email' => $email])->exists();
    }

    public function existsByPhone(string $phone): bool
    {
        return User::find()->where(['phone' => $phone])->exists();
    }

    public function save(User $user): void
    {
        if (!$user->save()) {
            $errors = implode('; ', $user->getFirstErrors());
            throw new \RuntimeException($errors !== '' ? $errors : 'Unable to save user.');
        }
    }
}
