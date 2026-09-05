<?php

declare(strict_types=1);

namespace app\models;

use app\components\BookCoverStorage;
use app\models\query\SoftDeleteQuery;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $title
 * @property int $year
 * @property string|null $description
 * @property string $isbn
 * @property string|null $image
 * @property int $created_by
 * @property string $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 *
 * @property-read BookAuthor[] $authors
 * @property-read User $createdBy
 */
class Book extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%book}}';
    }

    public static function find(): SoftDeleteQuery
    {
        return new SoftDeleteQuery(static::class);
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn', 'created_by'], 'required'],
            ['title', 'string', 'max' => 255],
            ['isbn', 'string', 'max' => 32],
            ['description', 'string'],
            ['image', 'string', 'max' => 45],
            [['year', 'created_by'], 'integer'],
            ['created_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Title',
            'year' => 'Year',
            'description' => 'Description',
            'isbn' => 'ISBN',
            'image' => 'Cover',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
            'created_by' => 'Created by',
        ];
    }

    public function getCoverUrl(?int $size = null): ?string
    {
        if ($this->image === null || $this->image === '') {
            return null;
        }

        return BookCoverStorage::url($this->image, $size);
    }

    public function getAuthorNames(): string
    {
        return implode(', ', array_map(
            static fn (BookAuthor $author) => $author->getFullName(),
            $this->authors,
        ));
    }

    /**
     * @return int[]
     */
    public function getAuthorIds(): array
    {
        return array_map(static fn (BookAuthor $author) => $author->id, $this->authors);
    }

    public function getAuthors(): ActiveQuery
    {
        /** @var SoftDeleteQuery $query */
        $query = $this->hasMany(BookAuthor::class, ['id' => 'book_author_id'])
            ->viaTable('{{%book_book_author}}', ['book_id' => 'id']);

        return $query->includeDeleted();
    }

    public function getCreatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
