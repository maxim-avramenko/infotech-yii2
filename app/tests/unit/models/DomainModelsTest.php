<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Book;
use app\models\BookAuthor;
use app\models\BookAuthorForm;
use app\models\BookAuthorSubscription;
use app\models\BookAuthorSubscriptionForm;
use app\models\BookForm;
use app\models\ContactForm;
use app\models\SmsNotification;
use app\models\UserRole;
use app\models\query\SoftDeleteQuery;
use tests\unit\DbTestCase;
use Yii;

class DomainModelsTest extends DbTestCase
{
    public function testContactFormSendsEmailAndRejectsInvalid(): void
    {
        $invalid = new ContactForm();
        verify($invalid->contact('admin@example.com'))->false();

        $model = new ContactForm();
        $model->attributes = [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'subject' => 'Subject',
            'body' => 'Body',
            'verifyCode' => 'testme',
        ];
        verify($model->contact('admin@example.com'))->true();
        $this->tester->seeEmailIsSent();
        verify($model->attributeLabels()['verifyCode'])->equals('Verification Code');
    }

    public function testBookAuthorNamesAndSoftDeleteQuery(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user, ['lastname' => 'Pushkin', 'firstname' => 'Alexander', 'secondname' => null]);
        verify($author->getFullName())->equals('Pushkin Alexander');
        verify($author->attributeLabels()['lastname'])->equals('Last name');
        verify($author->tableName())->equals('{{%book_author}}');
        verify(BookAuthor::find())->instanceOf(SoftDeleteQuery::class);

        $author->deleted_at = date('Y-m-d H:i:s');
        $author->save(false, ['deleted_at']);
        verify(BookAuthor::findOne($author->id))->null();
        verify(BookAuthor::find()->includeDeleted()->andWhere(['id' => $author->id])->one())->instanceOf(BookAuthor::class);
        verify($author->getCreatedBy()->one()->id)->equals($user->id);
    }

    public function testBookCoverUrlAuthorsAndCreatedBy(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id], ['image' => 'cover.jpeg']);
        verify($book->getCoverUrl())->stringContainsString('books/cover.jpeg');
        verify($book->getCoverUrl(60))->stringContainsString('cover_60.jpeg');
        $book->image = '';
        verify($book->getCoverUrl())->null();
        verify($book->getAuthorNames())->equals($author->getFullName());
        verify($book->getAuthorIds())->equals([(int) $author->id]);
        verify($book->getCreatedBy()->one()->id)->equals($user->id);
        verify($book->attributeLabels()['isbn'])->equals('ISBN');
        verify($book->tableName())->equals('{{%book}}');
        verify(Book::find())->instanceOf(SoftDeleteQuery::class);
    }

    public function testBookFormFromBookLoadAndNormalizedIds(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $book = $this->createBook($user, [(int) $author->id], ['description' => null, 'year' => 2020]);
        $form = BookForm::fromBook($book);
        verify($form->year)->equals('2020');
        verify($form->description)->equals('');
        verify($form->normalizedAuthorIds())->equals([(int) $author->id]);
        verify($form->attributeLabels()['authorIds'])->equals('Authors');

        $empty = new BookForm();
        $empty->authorIds = ['1', '1', '0', '2'];
        verify($empty->normalizedAuthorIds())->equals([1, 2]);
        $empty->load(['BookForm' => ['title' => 'T', 'year' => '2024', 'isbn' => 'i']]);
        verify($empty->authorIds)->equals([]);
        $empty->title = 'T';
        $empty->year = (int) date('Y');
        $empty->isbn = 'isbn';
        $empty->authorIds = [(int) $author->id];
        verify($empty->validate())->true();
    }

    public function testAuthorAndSubscriptionForms(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user, ['secondname' => null]);
        $form = BookAuthorForm::fromAuthor($author);
        verify($form->secondname)->equals('');
        $form->lastname = 'L';
        $form->firstname = 'F';
        verify($form->validate())->true();
        verify($form->attributeLabels()['secondname'])->equals('Patronymic');

        $subForm = new BookAuthorSubscriptionForm();
        verify($subForm->validate())->false();
        $subForm->bookAuthorId = $author->id;
        $subForm->phone = '79001234567';
        verify($subForm->validate())->true();
        verify($subForm->attributeLabels()['phone'])->equals('Phone');

        $author->deleted_at = date('Y-m-d H:i:s');
        $author->save(false, ['deleted_at']);
        $deletedForm = new BookAuthorSubscriptionForm();
        $deletedForm->bookAuthorId = $author->id;
        $deletedForm->phone = '79001234567';
        verify($deletedForm->validate())->false();
    }

    public function testSubscriptionAndSmsNotification(): void
    {
        $user = $this->createUser();
        $author = $this->createAuthor($user);
        $sub = $this->createSubscription((int) $author->id, '79001234567');
        verify($sub->tableName())->equals('{{%book_author_subscription}}');
        verify($sub->getAuthor()->one()->id)->equals($author->id);

        $sms = $this->createSmsNotification(['phone' => '79001234567', 'text' => 'x']);
        verify($sms->tableName())->equals('{{%sms_notification}}');
        verify($sms->status)->equals(SmsNotification::STATUS_PENDING);
        verify(UserRole::cases())->arrayCount(2);
    }
}
