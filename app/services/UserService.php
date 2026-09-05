<?php

declare(strict_types=1);

namespace app\services;

use app\events\UserLoggedInEvent;
use app\exceptions\DuplicateUserException;
use app\models\User;
use app\models\UserRole;
use app\repositories\UserRepository;
use InvalidArgumentException;
use Yii;
use yii\base\Security;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Security $security,
    ) {
    }

    public function createAdmin(string $email, string $phone, string $password): User
    {
        return $this->create($email, $phone, $password, UserRole::Administrator, verifyContacts: true);
    }

    public function register(string $email, string $phone, string $password): User
    {
        return $this->create($email, $phone, $password, UserRole::User, verifyContacts: false);
    }

    public function authenticate(string $login, string $password): ?User
    {
        $user = $this->findByLogin($login);
        if ($user === null || !$user->validatePassword($password)) {
            return null;
        }

        return $user;
    }

    public function signIn(
        string $login,
        string $password,
        bool $rememberMe = false,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): bool {
        $user = $this->authenticate($login, $password);
        if ($user === null) {
            return false;
        }

        $duration = $rememberMe ? 3600 * 24 * 30 : 0;
        if (!Yii::$app->user->login($user, $duration)) {
            return false;
        }

        $this->notifyLoggedIn($user, $ipAddress, $userAgent);

        return true;
    }

    public function findByLogin(string $login): ?User
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        if (str_contains($login, '@')) {
            return $this->users->findByEmail(mb_strtolower($login));
        }

        $phone = $this->digits($login);
        if ($phone === '') {
            return null;
        }

        return $this->users->findByPhone($phone);
    }

    private function notifyLoggedIn(User $user, ?string $ipAddress, ?string $userAgent): void
    {
        $email = trim((string) $user->email);
        if ($email === '') {
            return;
        }

        try {
            Yii::$app->trigger(UserLoggedInEvent::NAME, new UserLoggedInEvent(
                (int) $user->id,
                $email,
                (new \DateTimeImmutable())->format('d.m.Y H:i:s T'),
                $this->normalizeIp($ipAddress),
                $this->browserName($userAgent),
            ));
        } catch (\Throwable $exception) {
            Yii::error($exception, __METHOD__);
        }
    }

    private function normalizeIp(?string $ipAddress): string
    {
        $ip = trim((string) $ipAddress);

        return $ip !== '' ? $ip : 'unknown';
    }

    private function browserName(?string $userAgent): string
    {
        $userAgent = trim((string) $userAgent);
        if ($userAgent === '') {
            return 'unknown';
        }

        $map = [
            'Edg/' => 'Microsoft Edge',
            'OPR/' => 'Opera',
            'Opera' => 'Opera',
            'Firefox/' => 'Firefox',
            'CriOS/' => 'Chrome',
            'Chrome/' => 'Chrome',
            'Safari/' => 'Safari',
        ];
        foreach ($map as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                return $name;
            }
        }

        return $userAgent;
    }

    private function create(
        string $email,
        string $phone,
        string $password,
        UserRole $role,
        bool $verifyContacts,
    ): User {
        $email = $this->normalizeEmail($email);
        $phone = $this->normalizePhone($phone);
        $this->assertPassword($password);
        $this->assertUnique($email, $phone);

        $user = new User();
        $user->email = $email;
        $user->phone = $phone;
        $user->password_hash = $this->security->generatePasswordHash($password);
        $user->auth_key = $this->security->generateRandomString();
        $user->role = $role->value;

        if ($verifyContacts) {
            $now = date('Y-m-d H:i:s');
            $user->email_verified_at = $now;
            $user->phone_verified_at = $now;
        }

        $this->users->save($user);

        return $user;
    }

    private function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email.');
        }

        return $email;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = $this->digits($phone);
        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            throw new InvalidArgumentException('Phone must contain 10 to 15 digits.');
        }

        return $digits;
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function assertPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }
    }

    private function assertUnique(string $email, string $phone): void
    {
        if ($this->users->existsByEmail($email)) {
            throw new DuplicateUserException('A user with this email already exists.');
        }
        if ($this->users->existsByPhone($phone)) {
            throw new DuplicateUserException('A user with this phone already exists.');
        }
    }
}
