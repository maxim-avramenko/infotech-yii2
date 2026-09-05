<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\components\BookCoverStorage;
use app\events\BookCreatedEvent;
use app\models\Book;
use app\repositories\BookAuthorRepository;
use app\repositories\BookRepository;
use app\services\BookService;
use InvalidArgumentException;
use tests\unit\DbTestCase;
use Yii;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;

class BookServiceTest extends DbTestCase
{
    private BookService $service;
    private BookCoverStorage $covers;

    protected function _before(): void
    {
        parent::_before();
        $this->covers = $this->createMock(BookCoverStorage::class);
        $this->service = new BookService(new BookRepository(), new BookAuthorRepository(), $this->covers);
    }

    public function testCreateRequiresTitleIsbnAndAuthor(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);

        try {
            $this->service->create('  ', 2024, null, 'isbn', [(int) $author->id], $user);
            $this->fail('Expected title exception');
        } catch (InvalidArgumentException $exception) {
            verify($exception->getMessage())->equals('Title is required.');
        }

        try {
            $this->service->create('Title', 2024, null, '  ', [(int) $author->id], $user);
            $this->fail('Expected ISBN exception');
        } catch (InvalidArgumentException $exception) {
            verify($exception->getMessage())->equals('ISBN is required.');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->service->create('Title', 2024, null, 'isbn', [], $user);
    }

    public function testCreateSavesBookTriggersEventAndStoresCover(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $this->covers->expects($this->once())->method('store')->willReturn('cover.jpeg');
        $captured = null;
        Yii::$app->on(BookCreatedEvent::NAME, static function (Event $event) use (&$captured): void {
            $captured = $event;
        });

        $file = $this->createMock(UploadedFile::class);
        $book = $this->service->create('  Title  ', 2024, '  desc  ', '  isbn-1  ', [(int) $author->id, 0, (int) $author->id], $user, $file);

        verify($book->title)->equals('Title');
        verify($book->description)->equals('desc');
        verify($book->isbn)->equals('isbn-1');
        verify($book->image)->equals('cover.jpeg');
        verify($book->getAuthorIds())->equals([(int) $author->id]);
        verify($captured)->instanceOf(BookCreatedEvent::class);
        verify($captured->bookId)->equals((int) $book->id);
        verify($this->service->list())->instanceOf(ActiveDataProvider::class);
        verify($this->service->findById((int) $book->id))->instanceOf(Book::class);
    }

    public function testCreateDeletesCoverWhenSaveFails(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $books = $this->createMock(BookRepository::class);
        $books->method('save')->willThrowException(new \RuntimeException('fail'));
        $covers = $this->createMock(BookCoverStorage::class);
        $covers->method('store')->willReturn('cover.jpeg');
        $covers->expects($this->once())->method('delete')->with('cover.jpeg');
        $service = new BookService($books, new BookAuthorRepository(), $covers);

        $this->expectException(\RuntimeException::class);
        $service->create('Title', 2024, null, 'isbn', [(int) $author->id], $user, $this->createMock(UploadedFile::class));
    }

    public function testCreateSwallowsEventErrors(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        Yii::$app->on(BookCreatedEvent::NAME, static function (): void {
            throw new \RuntimeException('boom');
        });

        $book = $this->service->create('Title', 2024, '', 'isbn', [(int) $author->id], $user);
        verify($book->description)->null();
    }

    public function testUpdateReplacesCoverAndAllowsExistingDeletedAuthor(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id], ['image' => 'old.jpeg']);
        $author->deleted_at = date('Y-m-d H:i:s');
        $author->save(false, ['deleted_at']);

        $this->covers->method('store')->willReturn('new.jpeg');
        $this->covers->expects($this->once())->method('delete')->with('old.jpeg');

        $updated = $this->service->update($book, 'New', 2025, null, 'isbn-2', [(int) $author->id], $this->createMock(UploadedFile::class));
        verify($updated->title)->equals('New');
        verify($updated->image)->equals('new.jpeg');
    }

    public function testUpdateDeletesNewCoverWhenSaveFails(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id], ['image' => 'old.jpeg']);
        $books = $this->createMock(BookRepository::class);
        $books->method('save')->willThrowException(new \RuntimeException('fail'));
        $covers = $this->createMock(BookCoverStorage::class);
        $covers->method('store')->willReturn('new.jpeg');
        $covers->expects($this->once())->method('delete')->with('new.jpeg');
        $service = new BookService($books, new BookAuthorRepository(), $covers);

        $this->expectException(\RuntimeException::class);
        $service->update($book, 'New', 2025, null, 'isbn-2', [(int) $author->id], $this->createMock(UploadedFile::class));
    }

    public function testDeleteSoftDeletes(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);
        $this->service->delete($book);
        verify($this->service->findById((int) $book->id))->null();
    }

    public function testAuthorOptionsKeepSelectedOrderAndIncludeDeleted(): void
    {
        $user = $this->createUser();
        $first = $this->createAuthor($user, ['lastname' => 'A', 'firstname' => 'One']);
        $second = $this->createAuthor($user, ['lastname' => 'B', 'firstname' => 'Two']);
        $second->deleted_at = date('Y-m-d H:i:s');
        $second->save(false, ['deleted_at']);

        $options = $this->service->authorOptions([(int) $second->id, (int) $first->id, 0]);
        verify(array_keys($options))->equals([(int) $first->id, (int) $second->id]);
        verify($options[(int) $second->id])->equals($second->getFullName());
    }

    public function testRejectsUnknownAuthorOnCreate(): void
    {
        $user = $this->createUser();
        $this->expectException(InvalidArgumentException::class);
        $this->service->create('Title', 2024, null, 'isbn', [1], $user);
    }
}
