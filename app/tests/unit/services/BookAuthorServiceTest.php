<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\models\BookAuthor;
use app\repositories\BookAuthorRepository;
use app\services\BookAuthorService;
use InvalidArgumentException;
use tests\unit\DbTestCase;
use yii\data\ActiveDataProvider;

class BookAuthorServiceTest extends DbTestCase
{
    private BookAuthorService $service;

    protected function _before(): void
    {
        parent::_before();
        $this->service = new BookAuthorService(new BookAuthorRepository());
    }

    public function testCreateNormalizesNames(): void
    {
        $user = $this->createUser();
        $author = $this->service->create("  Doe  ", " Jane\n", "  Ann  ", $user);

        verify($author->lastname)->equals('Doe');
        verify($author->firstname)->equals('Jane');
        verify($author->secondname)->equals('Ann');
        verify($author->created_by)->equals($user->id);
    }

    public function testCreateRequiresLastName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create('   ', 'Jane', null, $this->createUser());
    }

    public function testCreateRequiresFirstName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create('Doe', '', null, $this->createUser());
    }

    public function testOptionalSecondNameBecomesNull(): void
    {
        $author = $this->service->create('Doe', 'Jane', '   ', $this->createUser());
        verify($author->secondname)->null();
    }

    public function testUpdateAndDelete(): void
    {
        $user = $this->createUser();
        $author = $this->service->create('Doe', 'Jane', null, $user);
        $updated = $this->service->update($author, 'Smith', 'John', 'Q');
        verify($updated->lastname)->equals('Smith');
        verify($updated->firstname)->equals('John');

        $this->service->delete($author);
        verify($this->service->findById((int) $author->id))->null();
    }

    public function testListSearchTopAndYears(): void
    {
        $user = $this->createUser();
        $author = $this->service->create('Tolstoy', 'Leo', null, $user);
        $this->createBook($user, [(int) $author->id], ['year' => 2020, 'title' => 'War']);

        verify($this->service->list())->instanceOf(ActiveDataProvider::class);
        verify($this->service->findById((int) $author->id))->instanceOf(BookAuthor::class);
        verify($this->service->search('tolst'))->notEmpty();
        verify($this->service->yearsWithBooks())->equals([2020]);
        verify($this->service->topByYear(2020)[0]['lastname'])->equals('Tolstoy');
        verify($this->service->books($author))->instanceOf(ActiveDataProvider::class);
    }

    public function testSearchTruncatesLongQuery(): void
    {
        $query = str_repeat('a', 120);
        verify($this->service->search($query))->equals([]);
    }
}
