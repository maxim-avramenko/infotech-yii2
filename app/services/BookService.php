<?php

declare(strict_types=1);

namespace app\services;

use app\components\BookCoverStorage;
use app\events\BookCreatedEvent;
use app\models\Book;
use app\models\User;
use app\repositories\BookAuthorRepository;
use app\repositories\BookRepository;
use InvalidArgumentException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;

class BookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly BookAuthorRepository $authors,
        private readonly BookCoverStorage $covers,
    ) {
    }

    public function list(): ActiveDataProvider
    {
        return $this->books->dataProvider();
    }

    public function findById(int $id): ?Book
    {
        return $this->books->findById($id);
    }

    /**
     * @param int[] $selectedIds
     * @return array<int, string>
     */
    public function authorOptions(array $selectedIds): array
    {
        $ids = $this->normalizeAuthorIds($selectedIds);
        $found = $this->authors->findByIdsIncludingDeleted($ids);
        $options = [];
        foreach ($ids as $id) {
            if (isset($found[$id])) {
                $options[$id] = $found[$id]->getFullName();
            }
        }

        return $options;
    }

    /**
     * @param int[] $authorIds
     */
    public function create(
        string $title,
        int $year,
        ?string $description,
        string $isbn,
        array $authorIds,
        User $createdBy,
        ?UploadedFile $image = null,
    ): Book {
        $book = new Book();
        $authorIds = $this->applyAttributes($book, $title, $year, $description, $isbn, $authorIds);
        $book->created_by = $createdBy->id;

        if ($image !== null) {
            $book->image = $this->covers->store($image);
        }

        try {
            $this->books->save($book, $authorIds);
        } catch (\Throwable $exception) {
            if ($book->image !== null) {
                $this->covers->delete($book->image);
            }
            throw $exception;
        }

        try {
            Yii::$app->trigger(BookCreatedEvent::NAME, new BookCreatedEvent((int) $book->id));
        } catch (\Throwable $exception) {
            Yii::error($exception, __METHOD__);
        }

        return $book;
    }

    /**
     * @param int[] $authorIds
     */
    public function update(
        Book $book,
        string $title,
        int $year,
        ?string $description,
        string $isbn,
        array $authorIds,
        ?UploadedFile $image = null,
    ): Book {
        $authorIds = $this->applyAttributes($book, $title, $year, $description, $isbn, $authorIds);
        $previousImage = $book->image;

        if ($image !== null) {
            $book->image = $this->covers->store($image);
        }

        try {
            $this->books->save($book, $authorIds);
        } catch (\Throwable $exception) {
            if ($image !== null && $book->image !== $previousImage) {
                $this->covers->delete($book->image);
            }
            throw $exception;
        }

        if ($image !== null && $previousImage !== null && $previousImage !== $book->image) {
            $this->covers->delete($previousImage);
        }

        return $book;
    }

    public function delete(Book $book): void
    {
        $this->books->delete($book);
    }

    /**
     * @param int[] $authorIds
     * @return int[]
     */
    private function applyAttributes(
        Book $book,
        string $title,
        int $year,
        ?string $description,
        string $isbn,
        array $authorIds,
    ): array {
        $title = trim($title);
        $isbn = trim($isbn);
        $description = $description === null ? null : trim($description);
        if ($title === '') {
            throw new InvalidArgumentException('Title is required.');
        }
        if ($isbn === '') {
            throw new InvalidArgumentException('ISBN is required.');
        }

        $authorIds = $this->normalizeAuthorIds($authorIds);
        $this->assertAuthors($book, $authorIds);

        $book->title = $title;
        $book->year = $year;
        $book->description = $description === '' ? null : $description;
        $book->isbn = $isbn;

        return $authorIds;
    }

    /**
     * @param int[] $authorIds
     * @return int[]
     */
    private function normalizeAuthorIds(array $authorIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $authorIds),
            static fn (int $id) => $id > 0,
        )));
        sort($ids);

        return $ids;
    }

    /**
     * @param int[] $authorIds
     */
    private function assertAuthors(Book $book, array $authorIds): void
    {
        if ($authorIds === []) {
            throw new InvalidArgumentException('At least one author is required.');
        }

        $live = $this->authors->findByIds($authorIds);
        $missing = array_diff($authorIds, array_map('intval', array_keys($live)));
        if ($missing === []) {
            return;
        }

        $currentIds = $book->isNewRecord ? [] : $book->getAuthorIds();
        $allowedDeleted = array_intersect($missing, $currentIds);
        $deleted = $this->authors->findByIdsIncludingDeleted($allowedDeleted);
        if (count($deleted) !== count($missing)) {
            throw new InvalidArgumentException('Author is required.');
        }
    }
}
