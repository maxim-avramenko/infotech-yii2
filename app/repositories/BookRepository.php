<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\Book;
use yii\data\ActiveDataProvider;

class BookRepository
{
    public function findById(int $id): ?Book
    {
        return Book::find()->with(['authors', 'createdBy'])->andWhere(['id' => $id])->one();
    }

    public function dataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Book::find()->with(['authors', 'createdBy']),
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    /**
     * @param int[] $authorIds
     */
    public function save(Book $book, array $authorIds): void
    {
        $transaction = Book::getDb()->beginTransaction();
        try {
            if (!$book->save()) {
                $errors = implode('; ', $book->getFirstErrors());
                throw new \RuntimeException($errors !== '' ? $errors : 'Unable to save book.');
            }
            $this->syncAuthors($book, $authorIds);
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    public function delete(Book $book): void
    {
        $book->deleted_at = date('Y-m-d H:i:s');
        if ($book->save(false, ['deleted_at', 'updated_at']) === false) {
            throw new \RuntimeException('Unable to delete book.');
        }
    }

    /**
     * @param int[] $authorIds
     */
    private function syncAuthors(Book $book, array $authorIds): void
    {
        $db = Book::getDb();
        $db->createCommand()->delete('{{%book_book_author}}', ['book_id' => $book->id])->execute();
        foreach ($authorIds as $authorId) {
            $db->createCommand()->insert('{{%book_book_author}}', [
                'book_id' => $book->id,
                'book_author_id' => $authorId,
            ])->execute();
        }
    }
}
