<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\events\UserLoggedInEvent;
use app\exceptions\DuplicateUserException;
use app\models\User;
use app\models\UserRole;
use app\repositories\UserRepository;
use app\services\UserService;
use InvalidArgumentException;
use tests\unit\DbTestCase;
use Yii;
use yii\base\Event;
use yii\base\Security;

class UserServiceTest extends DbTestCase
{
    private UserService $service;

    protected function _before(): void
    {
        parent::_before();
        $this->service = new UserService(new UserRepository(), Yii::$app->security);
        Yii::$app->user->logout();
    }

    protected function _after(): void
    {
        Yii::$app->user->logout();
        parent::_after();
    }

    public function testCreateAdminPersistsVerifiedAdministrator(): void
    {
        $user = $this->service->createAdmin('Admin@Example.com', '+7 (900) 111-22-33', 'password123');

        verify($user->id)->notEmpty();
        verify($user->email)->equals('admin@example.com');
        verify($user->phone)->equals('79001112233');
        verify($user->role)->equals(UserRole::Administrator->value);
        verify($user->email_verified_at)->notEmpty();
        verify($user->phone_verified_at)->notEmpty();
        verify($user->validatePassword('password123'))->true();
    }

    public function testRegisterDoesNotVerifyContacts(): void
    {
        $user = $this->service->register('user@example.com', '79001112233', 'password123');

        verify($user->role)->equals(UserRole::User->value);
        verify($user->email_verified_at)->empty();
        verify($user->phone_verified_at)->empty();
    }

    public function testCreateRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->register('not-an-email', '79001112233', 'password123');
    }

    public function testCreateRejectsShortPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->register('user@example.com', '123', 'password123');
    }

    public function testCreateRejectsShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->register('user@example.com', '79001112233', 'short');
    }

    public function testCreateRejectsDuplicateEmail(): void
    {
        $this->service->register('user@example.com', '79001112233', 'password123');
        $this->expectException(DuplicateUserException::class);
        $this->service->register('user@example.com', '79009998877', 'password123');
    }

    public function testCreateRejectsDuplicatePhone(): void
    {
        $this->service->register('one@example.com', '79001112233', 'password123');
        $this->expectException(DuplicateUserException::class);
        $this->service->register('two@example.com', '79001112233', 'password123');
    }

    public function testFindByLoginSupportsEmailAndPhone(): void
    {
        $this->service->register('user@example.com', '79001112233', 'password123');

        verify($this->service->findByLogin('  USER@example.com ')?->email)->equals('user@example.com');
        verify($this->service->findByLogin('+7-900-111-22-33')?->phone)->equals('79001112233');
        verify($this->service->findByLogin(''))->null();
        verify($this->service->findByLogin('abc'))->null();
        verify($this->service->findByLogin('missing@example.com'))->null();
    }

    public function testAuthenticateReturnsNullOnBadPassword(): void
    {
        $this->service->register('user@example.com', '79001112233', 'password123');

        verify($this->service->authenticate('user@example.com', 'wrong-password'))->null();
        verify($this->service->authenticate('user@example.com', 'password123'))->instanceOf(User::class);
    }

    public function testSignInFailsForUnknownUser(): void
    {
        verify($this->service->signIn('nobody@example.com', 'password123'))->false();
        verify(Yii::$app->user->isGuest)->true();
    }

    public function testSignInLogsUserInAndTriggersEvent(): void
    {
        $this->service->register('user@example.com', '79001112233', 'password123');
        $captured = null;
        Yii::$app->on(UserLoggedInEvent::NAME, static function (Event $event) use (&$captured): void {
            $captured = $event;
        });

        verify($this->service->signIn(
            'user@example.com',
            'password123',
            true,
            '127.0.0.1',
            'Mozilla/5.0 Chrome/120.0.0.0 Safari/537.36',
        ))->true();
        verify(Yii::$app->user->isGuest)->false();
        verify($captured)->instanceOf(UserLoggedInEvent::class);
        verify($captured->ip)->equals('127.0.0.1');
        verify($captured->browser)->equals('Chrome');
    }

    /**
     * @dataProvider browserProvider
     */
    public function testBrowserNameMapping(string $userAgent, string $expected): void
    {
        $this->service->register('user@example.com', '79001112233', 'password123');
        $captured = null;
        Yii::$app->on(UserLoggedInEvent::NAME, static function (Event $event) use (&$captured): void {
            $captured = $event;
        });

        $this->service->signIn('user@example.com', 'password123', false, '', $userAgent);

        verify($captured)->instanceOf(UserLoggedInEvent::class);
        verify($captured->browser)->equals($expected);
        verify($captured->ip)->equals('unknown');
    }

    public static function browserProvider(): array
    {
        return [
            'empty' => ['', 'unknown'],
            'edge' => ['Mozilla/5.0 Edg/120.0', 'Microsoft Edge'],
            'opera-opr' => ['Mozilla/5.0 OPR/90.0', 'Opera'],
            'opera' => ['Opera/9.80', 'Opera'],
            'firefox' => ['Mozilla/5.0 Firefox/120.0', 'Firefox'],
            'crios' => ['CriOS/120.0', 'Chrome'],
            'safari' => ['Mozilla/5.0 Version/17.0 Safari/605.1.15', 'Safari'],
            'custom' => ['CustomAgent/1.0', 'CustomAgent/1.0'],
        ];
    }

    public function testNotifyLoggedInSwallowsHandlerErrors(): void
    {
        $this->service->register('user@example.com', '79001112233', 'password123');
        Yii::$app->on(UserLoggedInEvent::NAME, static function (): void {
            throw new \RuntimeException('handler failed');
        });

        verify($this->service->signIn('user@example.com', 'password123'))->true();
    }

    public function testCreateUsesSecurityComponent(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('existsByEmail')->willReturn(false);
        $users->method('existsByPhone')->willReturn(false);
        $users->expects($this->once())->method('save')->with($this->callback(static function (User $user): bool {
            return $user->password_hash === 'hashed' && $user->auth_key === 'key';
        }));

        $security = $this->createMock(Security::class);
        $security->method('generatePasswordHash')->willReturn('hashed');
        $security->method('generateRandomString')->willReturn('key');

        $service = new UserService($users, $security);
        $user = $service->register('user@example.com', '79001112233', 'password123');
        verify($user->password_hash)->equals('hashed');
    }
}
