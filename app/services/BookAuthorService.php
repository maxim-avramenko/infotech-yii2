<?php

declare(strict_types=1);

namespace app\services;

use app\models\BookAuthor;
use app\models\User;
use app\repositories\BookAuthorRepository;
use InvalidArgumentException;
use yii\data\ActiveDataProvider;

class BookAuthorService
{
    public function __construct(
        private readonly BookAuthorRepository $authors,
    ) {
    }

    public function list(): ActiveDataProvider
    {
        return $this->authors->dataProvider();
    }

    /**
     * @return list<array{id: int|string, lastname: string, firstname: string, secondname: string|null, book_count: int|string}>
     */
    public function topByYear(int $year, int $limit = 10): array
    {
        return $this->authors->topByYear($year, $limit);
    }

    /**
     * @return int[]
     */
    public function yearsWithBooks(): array
    {
        return $this->authors->yearsWithBooks();
    }

    public function books(BookAuthor $author): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $author->getBooks()->with(['authors', 'createdBy']),
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => false,
        ]);
    }

    public function findById(int $id): ?BookAuthor
    {
        return $this->authors->findById($id);
    }

    /**
     * @return list<array{id: string, text: string}>
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (mb_strlen($query) > 100) {
            $query = mb_substr($query, 0, 100);
        }

        return $this->authors->search($query, $limit);
    }

    public function create(string $lastname, string $firstname, ?string $secondname, User $createdBy): BookAuthor
    {
        $author = new BookAuthor();
        $this->applyNames($author, $lastname, $firstname, $secondname);
        $author->created_by = $createdBy->id;
        $this->authors->save($author);

        return $author;
    }

    public function update(BookAuthor $author, string $lastname, string $firstname, ?string $secondname): BookAuthor
    {
        $this->applyNames($author, $lastname, $firstname, $secondname);
        $this->authors->save($author);

        return $author;
    }

    public function delete(BookAuthor $author): void
    {
        $this->authors->delete($author);
    }

    private function applyNames(BookAuthor $author, string $lastname, string $firstname, ?string $secondname): void
    {
        $author->lastname = $this->normalizeName($lastname, 'Last name');
        $author->firstname = $this->normalizeName($firstname, 'First name');
        $author->secondname = $this->normalizeOptionalName($secondname);
    }

    private function normalizeName(string $name, string $label): string
    {
        $name = $this->collapseSpaces($name);
        if ($name === '') {
            throw new InvalidArgumentException("{$label} is required.");
        }

        return $name;
    }

    private function normalizeOptionalName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $name = $this->collapseSpaces($name);

        return $name === '' ? null : $name;
    }

    private function collapseSpaces(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    }
}
