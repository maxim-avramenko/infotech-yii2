<?php

declare(strict_types=1);

namespace app\models;

use app\components\BookCoverStorage;
use yii\base\Model;
use yii\web\UploadedFile;

class BookForm extends Model
{
    public string $title = '';
    public $year = '';
    public string $description = '';
    public string $isbn = '';
    /** @var int[]|string[] */
    public $authorIds = [];
    public $imageFile = null;
    /** @var int[] */
    public array $currentAuthorIds = [];

    public static function fromBook(Book $book): self
    {
        $form = new self();
        $form->title = $book->title;
        $form->year = (string) $book->year;
        $form->description = $book->description ?? '';
        $form->isbn = $book->isbn;
        $form->authorIds = $book->getAuthorIds();
        $form->currentAuthorIds = $form->authorIds;

        return $form;
    }

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'authorIds'], 'required'],
            ['title', 'string', 'max' => 255],
            ['isbn', 'string', 'max' => 32],
            ['description', 'string'],
            ['year', 'integer', 'min' => 1, 'max' => (int) date('Y') + 1],
            ['authorIds', 'each', 'rule' => ['integer']],
            [
                'authorIds',
                'each',
                'rule' => [
                    'exist',
                    'targetClass' => BookAuthor::class,
                    'targetAttribute' => 'id',
                    'filter' => function ($query) {
                        if ($this->currentAuthorIds === []) {
                            return;
                        }
                        $query->includeDeleted()->andWhere([
                            'or',
                            ['deleted_at' => null],
                            ['id' => $this->currentAuthorIds],
                        ]);
                    },
                ],
            ],
            [
                'imageFile',
                'file',
                'skipOnEmpty' => true,
                'maxSize' => BookCoverStorage::MAX_BYTES,
                'checkExtensionByMimeType' => false,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Title',
            'year' => 'Year',
            'description' => 'Description',
            'isbn' => 'ISBN',
            'authorIds' => 'Authors',
            'imageFile' => 'Cover',
        ];
    }

    /**
     * @return int[]
     */
    public function normalizedAuthorIds(): array
    {
        $ids = array_map('intval', (array) $this->authorIds);
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id) => $id > 0)));
        sort($ids);

        return $ids;
    }

    public function load($data, $formName = null): bool
    {
        $scope = $formName === null ? $this->formName() : $formName;
        $values = $scope === '' ? $data : ($data[$scope] ?? null);
        $loaded = parent::load($data, $formName);
        if (is_array($values) && !array_key_exists('authorIds', $values)) {
            $this->authorIds = [];
        }
        $this->loadImage();

        return $loaded;
    }

    public function loadImage(): void
    {
        $uploaded = UploadedFile::getInstance($this, 'imageFile');
        $this->imageFile = $uploaded instanceof UploadedFile && $uploaded->error === UPLOAD_ERR_OK
            ? $uploaded
            : null;
    }
}
