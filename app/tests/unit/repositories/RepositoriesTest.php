<?php

declare(strict_types=1);

namespace tests\unit\repositories;

use app\models\Book;
use app\models\SmsNotification;
use app\repositories\BookAuthorRepository;
use app\repositories\BookAuthorSubscriptionRepository;
use app\repositories\BookRepository;
use app\repositories\SmsNotificationRepository;
use app\repositories\UserRepository;
use tests\unit\DbTestCase;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class RepositoriesTest extends DbTestCase
{
    public function testUserRepository(): void
    {
        $repo = new UserRepository();
        $user = $this->createUser(['email' => 'find@example.com', 'phone' => '79001110000']);
        verify($repo->findById((int) $user->id)?->id)->equals($user->id);
        verify($repo->findByEmail('find@example.com')?->id)->equals($user->id);
        verify($repo->findByPhone('79001110000')?->id)->equals($user->id);
        verify($repo->existsByEmail('find@example.com'))->true();
        verify($repo->existsByPhone('79001110000'))->true();
        verify($repo->existsByEmail('missing@example.com'))->false();

        $user->email = 'not-an-email';
        $this->expectException(\RuntimeException::class);
        $repo->save($user);
    }

    public function testBookAuthorRepositorySearchTopAndDelete(): void
    {
        $user = $this->createUser();
        $alpha = $this->createAuthor($user, ['lastname' => 'Alpha', 'firstname' => 'Ann']);
        $beta = $this->createAuthor($user, ['lastname' => 'Beta', 'firstname' => 'Bob']);
        $this->createBook($user, [(int) $alpha->id], ['year' => 2021, 'title' => 'A1']);
        $this->createBook($user, [(int) $alpha->id], ['year' => 2021, 'title' => 'A2']);
        $this->createBook($user, [(int) $beta->id], ['year' => 2021, 'title' => 'B1']);
        $this->createBook($user, [(int) $alpha->id], ['year' => 2020, 'title' => 'Old', 'deleted_at' => date('Y-m-d H:i:s')]);

        $repo = new BookAuthorRepository();
        verify($repo->findById((int) $alpha->id)?->id)->equals($alpha->id);
        verify($repo->findByIds([]))->equals([]);
        verify($repo->findByIdsIncludingDeleted([]))->equals([]);
        verify(array_keys($repo->findByIds([(int) $alpha->id, (int) $beta->id])))->equals([(int) $alpha->id, (int) $beta->id]);
        verify($repo->yearsWithBooks())->equals([2021]);
        $top = $repo->topByYear(2021, 10);
        verify($top[0]['lastname'])->equals('Alpha');
        verify((int) $top[0]['book_count'])->equals(2);
        verify($repo->search('')[0]['text'])->stringContainsString('Alpha');
        verify($repo->search('Beta')[0]['id'])->equals((string) $beta->id);
        verify($repo->dataProvider())->instanceOf(ActiveDataProvider::class);

        $repo->delete($alpha);
        verify($repo->findById((int) $alpha->id))->null();
        verify($repo->findByIdIncludingDeleted((int) $alpha->id))->notEmpty();
        verify($repo->findByIdsIncludingDeleted([(int) $alpha->id]))->arrayHasKey($alpha->id);

        $alpha->lastname = '';
        $this->expectException(\RuntimeException::class);
        $repo->save($alpha);
    }

    public function testBookRepositorySaveSyncAndDelete(): void
    {
        $user = $this->createUser();
        $a = $this->createAuthor($user, ['lastname' => 'A']);
        $b = $this->createAuthor($user, ['lastname' => 'B']);
        $repo = new BookRepository();
        $book = new Book([
            'title' => 'T',
            'year' => 2024,
            'isbn' => 'isbn-x',
            'created_by' => $user->id,
        ]);
        $repo->save($book, [(int) $a->id, (int) $b->id]);
        verify($repo->findById((int) $book->id)?->getAuthorIds())->equals([(int) $a->id, (int) $b->id]);
        $repo->save($book, [(int) $b->id]);
        $book->refresh();
        verify($book->getAuthorIds())->equals([(int) $b->id]);
        $repo->delete($book);
        verify($repo->findById((int) $book->id))->null();
        verify($repo->dataProvider())->instanceOf(ActiveDataProvider::class);

        $invalid = new Book(['title' => '', 'year' => 1, 'isbn' => 'i', 'created_by' => $user->id]);
        $this->expectException(\RuntimeException::class);
        $repo->save($invalid, [(int) $a->id]);
    }

    public function testSubscriptionRepositoryDistinctPhonesAndIntegrity(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $repo = new BookAuthorSubscriptionRepository();
        verify($repo->findDistinctPhonesByAuthorIds([]))->equals([]);
        $this->createSubscription((int) $author->id, '79001111111');
        $this->createSubscription((int) $author->id, '79002222222');
        verify($repo->findByAuthorAndPhone((int) $author->id, '79001111111'))->notEmpty();
        $phones = $repo->findDistinctPhonesByAuthorIds([(int) $author->id]);
        sort($phones);
        verify($phones)->equals(['79001111111', '79002222222']);

        $dup = new \app\models\BookAuthorSubscription([
            'book_author_id' => $author->id,
            'phone' => '79001111111',
        ]);
        $repo->save($dup);
        verify($repo->findByAuthorAndPhone((int) $author->id, '79001111111'))->notEmpty();
    }

    public function testSmsNotificationRepositoryBatchDueSentAndPostpone(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id]);
        $repo = new SmsNotificationRepository();
        $repo->insertPendingBatch([]);
        $repo->insertPendingBatch([
            ['book_id' => (int) $book->id, 'phone' => '79001111111', 'text' => 'one'],
            ['book_id' => (int) $book->id, 'phone' => '79001111111', 'text' => 'dup'],
            ['book_id' => (int) $book->id, 'phone' => '79002222222', 'text' => 'two'],
        ]);
        verify(SmsNotification::find()->andWhere(['book_id' => $book->id])->count())->equals(2);

        $again = $repo->insertPendingOne('79001111111', 'later', (int) $book->id);
        verify($again->text)->equals('one');
        $plain = $repo->insertPendingOne('79003333333', 'plain');
        verify($plain->book_id)->null();

        $due = $repo->findDue(10);
        verify($due)->notEmpty();
        $repo->markSent($due[0]);
        $due[0]->refresh();
        verify($due[0]->status)->equals(SmsNotification::STATUS_SENT);

        $pending = $repo->insertPendingOne('79004444444', 'wait');
        $repo->postpone($pending, str_repeat('e', 300), 30);
        $pending->refresh();
        verify($pending->attempts)->equals(1);
        verify(mb_strlen((string) $pending->last_error))->equals(255);
        verify($pending->next_attempt_at)->notEmpty();
        verify(new Expression('NOW()'))->notEmpty();
    }
}
