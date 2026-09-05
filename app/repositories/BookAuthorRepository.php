<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\BookAuthor;
use yii\data\ActiveDataProvider;

class BookAuthorRepository
{
    /**
     * @return list<array{id: int|string, lastname: string, firstname: string, secondname: string|null, book_count: int|string}>
     */
    public function topByYear(int $year, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $db = BookAuthor::getDb();
        $authorTable = $db->quoteTableName('{{%book_author}}');
        $bookTable = $db->quoteTableName('{{%book}}');
        $linkTable = $db->quoteTableName('{{%book_book_author}}');

        $sql = <<<SQL
SELECT
    a.`id`,
    a.`lastname`,
    a.`firstname`,
    a.`secondname`,
    COUNT(*) AS `book_count`
FROM {$bookTable} b
INNER JOIN {$linkTable} bba ON bba.`book_id` = b.`id`
INNER JOIN {$authorTable} a ON a.`id` = bba.`book_author_id`
WHERE b.`year` = :year
  AND b.`deleted_at` IS NULL
  AND a.`deleted_at` IS NULL
GROUP BY a.`id`, a.`lastname`, a.`firstname`, a.`secondname`
ORDER BY `book_count` DESC, a.`lastname` ASC, a.`firstname` ASC, a.`id` ASC
LIMIT {$limit}
SQL;

        return $db->createCommand($sql, [':year' => $year])->queryAll();
    }

    /**
     * @return int[]
     */
    public function yearsWithBooks(): array
    {
        $db = BookAuthor::getDb();
        $bookTable = $db->quoteTableName('{{%book}}');
        $sql = <<<SQL
SELECT DISTINCT b.`year`
FROM {$bookTable} b
WHERE b.`deleted_at` IS NULL
ORDER BY b.`year` DESC
SQL;

        return array_map('intval', $db->createCommand($sql)->queryColumn());
    }

    public function findById(int $id): ?BookAuthor
    {
        return BookAuthor::findOne(['id' => $id]);
    }

    public function findByIdIncludingDeleted(int $id): ?BookAuthor
    {
        return BookAuthor::find()->includeDeleted()->andWhere(['id' => $id])->one();
    }

    /**
     * @param int[] $ids
     * @return BookAuthor[]
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return BookAuthor::find()->andWhere(['id' => $ids])->indexBy('id')->all();
    }

    /**
     * @param int[] $ids
     * @return BookAuthor[]
     */
    public function findByIdsIncludingDeleted(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return BookAuthor::find()->includeDeleted()->andWhere(['id' => $ids])->indexBy('id')->all();
    }

    public function dataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => BookAuthor::find()->with('createdBy'),
            'sort' => [
                'defaultOrder' => [
                    'lastname' => SORT_ASC,
                    'firstname' => SORT_ASC,
                ],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    /**
     * @return list<array{id: string, text: string}>
     */
    public function search(string $query, int $limit = 20): array
    {
        $q = BookAuthor::find()
            ->orderBy(['lastname' => SORT_ASC, 'firstname' => SORT_ASC])
            ->limit($limit);
        if ($query !== '') {
            $q->andWhere([
                'or',
                ['like', 'lastname', $query],
                ['like', 'firstname', $query],
                ['like', 'secondname', $query],
            ]);
        }
        $authors = $q->all();

        $options = [];
        foreach ($authors as $author) {
            $options[] = [
                'id' => (string) $author->id,
                'text' => $author->getFullName(),
            ];
        }

        return $options;
    }

    public function save(BookAuthor $author): void
    {
        if (!$author->save()) {
            $errors = implode('; ', $author->getFirstErrors());
            throw new \RuntimeException($errors !== '' ? $errors : 'Unable to save book author.');
        }
    }

    public function delete(BookAuthor $author): void
    {
        $author->deleted_at = date('Y-m-d H:i:s');
        if ($author->save(false, ['deleted_at']) === false) {
            throw new \RuntimeException('Unable to delete book author.');
        }
    }
}
