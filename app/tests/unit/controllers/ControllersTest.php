<?php

declare(strict_types=1);

namespace tests\unit\controllers;

use app\controllers\BookAuthorController;
use app\controllers\BookController;
use app\controllers\SiteController;
use app\models\BookAuthor;
use app\models\User;
use app\services\BookAuthorService;
use app\services\BookAuthorSubscriptionService;
use app\services\BookService;
use app\services\UserService;
use InvalidArgumentException;
use tests\unit\DbTestCase;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ControllersTest extends DbTestCase
{
    protected function _before(): void
    {
        parent::_before();
        $_SERVER['REQUEST_URI'] = '/index-test.php';
        $_SERVER['SCRIPT_NAME'] = '/index-test.php';
        $_SERVER['SCRIPT_FILENAME'] = Yii::getAlias('@app/web/index-test.php');
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
    }

    protected function attach(\yii\web\Controller $controller): void
    {
        Yii::$app->controller = $controller;
    }

    protected function _after(): void
    {
        Yii::$app->user->logout();
        parent::_after();
    }

    public function testSiteBehaviorsActionsAndPages(): void
    {
        $books = $this->createMock(BookService::class);
        $books->method('list')->willReturn(new ActiveDataProvider(['query' => \app\models\Book::find()]));
        $users = $this->createMock(UserService::class);
        $controller = new SiteController('site', Yii::$app, $users, $books);
        $this->attach($controller);

        verify($controller->behaviors())->arrayHasKey('access');
        verify($controller->actions())->arrayHasKey('captcha');
        verify($controller->actionIndex())->stringContainsString('<');
        verify($controller->actionAbout())->stringContainsString('<');
        verify($controller->actionContact())->stringContainsString('<');
        verify($controller->actionLogin())->stringContainsString('<');
        verify($controller->actionLogout())->instanceOf(Response::class);
    }

    public function testSiteLoginSuccessAndFailure(): void
    {
        $books = $this->createMock(BookService::class);
        $users = $this->createMock(UserService::class);
        $users->method('signIn')->willReturnOnConsecutiveCalls(true, false);
        $controller = new SiteController('site', Yii::$app, $users, $books);
        $this->attach($controller);

        Yii::$app->request->setBodyParams(['LoginForm' => [
            'login' => 'user@example.com',
            'password' => 'password123',
            'rememberMe' => '1',
        ]]);
        verify($controller->actionLogin())->instanceOf(Response::class);

        $html = $controller->actionLogin();
        verify($html)->stringContainsString('Incorrect email/phone or password');
    }

    public function testSiteLoginRedirectsWhenAlreadyAuthenticated(): void
    {
        $user = $this->createUser();
        Yii::$app->user->login($user);
        $controller = new SiteController(
            'site',
            Yii::$app,
            $this->createMock(UserService::class),
            $this->createMock(BookService::class),
        );
        $this->attach($controller);
        verify($controller->actionLogin())->instanceOf(Response::class);
    }

    public function testBookControllerCrud(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);
        Yii::$app->user->login($user);

        $service = $this->createMock(BookService::class);
        $service->method('findById')->willReturnCallback(static fn (int $id) => $id === (int) $book->id ? $book : null);
        $service->method('authorOptions')->willReturn([(int) $author->id => $author->getFullName()]);
        $service->method('create')->willReturn($book);
        $service->method('update')->willReturn($book);
        $service->expects($this->once())->method('delete');
        $controller = new BookController('book', Yii::$app, $service);
        $this->attach($controller);

        verify($controller->behaviors())->arrayHasKey('verbs');
        verify($controller->actionIndex())->instanceOf(Response::class);
        verify($controller->actionView((int) $book->id))->stringContainsString('<');
        verify($controller->actionCreate())->stringContainsString('<');
        verify($controller->actionUpdate((int) $book->id))->stringContainsString('<');
        verify($controller->actionDelete((int) $book->id))->instanceOf(Response::class);

        $this->expectException(NotFoundHttpException::class);
        $controller->actionView(999);
    }

    public function testBookCreateUpdateValidationErrors(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);
        Yii::$app->user->login($user);

        $service = $this->createMock(BookService::class);
        $service->method('findById')->willReturn($book);
        $service->method('authorOptions')->willReturn([]);
        $service->method('create')->willThrowException(new InvalidArgumentException('ISBN is required.'));
        $service->method('update')->willThrowException(new InvalidArgumentException('Image must be 10 MB or smaller.'));
        $controller = new BookController('book', Yii::$app, $service);
        $this->attach($controller);

        Yii::$app->request->setBodyParams(['BookForm' => [
            'title' => 'T',
            'year' => (string) date('Y'),
            'isbn' => 'isbn',
            'authorIds' => [(int) $author->id],
        ]]);
        verify($controller->actionCreate())->stringContainsString('ISBN is required');
        verify($controller->actionUpdate((int) $book->id))->stringContainsString('Image must be');
    }

    public function testBookCreateSuccessRedirects(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);
        Yii::$app->user->login($user);
        $service = $this->createMock(BookService::class);
        $service->method('create')->willReturn($book);
        $service->method('authorOptions')->willReturn([]);
        $controller = new BookController('book', Yii::$app, $service);
        $this->attach($controller);
        Yii::$app->request->setBodyParams(['BookForm' => [
            'title' => 'T',
            'year' => (string) date('Y'),
            'isbn' => 'isbn',
            'authorIds' => [(int) $author->id],
        ]]);
        verify($controller->actionCreate())->instanceOf(Response::class);
        Yii::$app->request->setBodyParams(['BookForm' => [
            'title' => 'T2',
            'year' => (string) date('Y'),
            'isbn' => 'isbn2',
            'authorIds' => [(int) $author->id],
        ]]);
        $service = $this->createMock(BookService::class);
        $service->method('findById')->willReturn($book);
        $service->method('update')->willReturn($book);
        $service->method('authorOptions')->willReturn([]);
        $controller = new BookController('book', Yii::$app, $service);
        $this->attach($controller);
        verify($controller->actionUpdate((int) $book->id))->instanceOf(Response::class);
    }

    public function testBookCreateRequiresAuthenticatedUser(): void
    {
        $author = $this->createAuthor($this->createUser());
        $service = $this->createMock(BookService::class);
        $service->method('authorOptions')->willReturn([]);
        $controller = new BookController('book', Yii::$app, $service);
        $this->attach($controller);
        Yii::$app->request->setBodyParams(['BookForm' => [
            'title' => 'T',
            'year' => (string) date('Y'),
            'isbn' => 'isbn',
            'authorIds' => [(int) $author->id],
        ]]);
        $this->expectException(ForbiddenHttpException::class);
        $controller->actionCreate();
    }

    public function testBookAuthorControllerActions(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        Yii::$app->user->login($user);
        $authors = $this->createMock(BookAuthorService::class);
        $authors->method('search')->willReturn([['id' => '1', 'text' => 'A']]);
        $authors->method('list')->willReturn(new ActiveDataProvider(['query' => BookAuthor::find()]));
        $authors->method('yearsWithBooks')->willReturn([2020]);
        $authors->method('topByYear')->willReturn([]);
        $authors->method('findById')->willReturnCallback(static fn (int $id) => $id === (int) $author->id ? $author : null);
        $authors->method('books')->willReturn(new ActiveDataProvider(['query' => \app\models\Book::find()]));
        $authors->method('create')->willReturn($author);
        $authors->method('update')->willReturn($author);
        $subs = $this->createMock(BookAuthorSubscriptionService::class);
        $controller = new BookAuthorController('book-author', Yii::$app, $authors, $subs);
        $this->attach($controller);

        verify($controller->behaviors())->arrayHasKey('access');
        verify($controller->actionSearch('q'))->equals([['id' => '1', 'text' => 'A']]);
        verify(Yii::$app->response->format)->equals(Response::FORMAT_JSON);
        verify($controller->actionIndex())->stringContainsString('<');
        verify($controller->actionTop(2020))->stringContainsString('<');
        verify($controller->actionTop(0))->stringContainsString('<');
        verify($controller->actionTop(1999))->stringContainsString('<');
        verify($controller->actionView((int) $author->id))->stringContainsString('<');
        verify($controller->actionCreate())->stringContainsString('<');
        verify($controller->actionUpdate((int) $author->id))->stringContainsString('<');
        verify($controller->actionDelete((int) $author->id))->instanceOf(Response::class);

        $this->expectException(NotFoundHttpException::class);
        $controller->actionView(999);
    }

    public function testBookAuthorSubscribeAndCreateUpdateErrors(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        Yii::$app->user->login($user);
        $authors = $this->createMock(BookAuthorService::class);
        $authors->method('findById')->willReturn($author);
        $authors->method('create')->willThrowException(new InvalidArgumentException('Last name is required.'));
        $authors->method('update')->willThrowException(new InvalidArgumentException('First name is required.'));
        $subs = $this->createMock(BookAuthorSubscriptionService::class);
        $subs->method('subscribe')->willThrowException(new InvalidArgumentException('Phone must contain 10 to 15 digits.'));
        $controller = new BookAuthorController('book-author', Yii::$app, $authors, $subs);
        $this->attach($controller);

        Yii::$app->request->setBodyParams([
            'BookAuthorSubscriptionForm' => ['bookAuthorId' => '', 'phone' => ''],
            'bookId' => 5,
        ]);
        verify($controller->actionSubscribe())->instanceOf(Response::class);

        Yii::$app->request->setBodyParams([
            'BookAuthorSubscriptionForm' => ['bookAuthorId' => $author->id, 'phone' => '79001234567'],
            'bookId' => 0,
        ]);
        verify($controller->actionSubscribe())->instanceOf(Response::class);

        Yii::$app->request->setBodyParams(['BookAuthorForm' => ['lastname' => 'L', 'firstname' => 'F']]);
        verify($controller->actionCreate())->stringContainsString('Last name is required');
        verify($controller->actionUpdate((int) $author->id))->stringContainsString('First name is required');
    }

    public function testBookAuthorCreateUpdateSuccessAndGuestForbidden(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        Yii::$app->user->login($user);
        $authors = $this->createMock(BookAuthorService::class);
        $authors->method('findById')->willReturn($author);
        $authors->method('create')->willReturn($author);
        $authors->method('update')->willReturn($author);
        $subs = $this->createMock(BookAuthorSubscriptionService::class);
        $subs->expects($this->once())->method('subscribe');
        $controller = new BookAuthorController('book-author', Yii::$app, $authors, $subs);
        $this->attach($controller);

        Yii::$app->request->setBodyParams(['BookAuthorForm' => ['lastname' => 'L', 'firstname' => 'F', 'secondname' => '']]);
        verify($controller->actionCreate())->instanceOf(Response::class);
        verify($controller->actionUpdate((int) $author->id))->instanceOf(Response::class);
        Yii::$app->request->setBodyParams([
            'BookAuthorSubscriptionForm' => ['bookAuthorId' => $author->id, 'phone' => '79001234567'],
        ]);
        verify($controller->actionSubscribe())->instanceOf(Response::class);

        Yii::$app->request->setBodyParams(['BookAuthorForm' => ['lastname' => 'L', 'firstname' => 'F', 'secondname' => '']]);
        Yii::$app->user->logout();
        $this->expectException(ForbiddenHttpException::class);
        $controller->actionCreate();
    }

    public function testBookAuthorTopWithEmptyYears(): void
    {
        $authors = $this->createMock(BookAuthorService::class);
        $authors->method('yearsWithBooks')->willReturn([]);
        $authors->method('topByYear')->willReturn([]);
        $controller = new BookAuthorController(
            'book-author',
            Yii::$app,
            $authors,
            $this->createMock(BookAuthorSubscriptionService::class),
        );
        $this->attach($controller);
        verify($controller->actionTop())->stringContainsString('<');
    }
}
