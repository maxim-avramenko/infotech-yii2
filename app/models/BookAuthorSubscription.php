<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $book_author_id
 * @property string $phone
 * @property string $created_at
 *
 * @property-read BookAuthor $author
 */
class BookAuthorSubscription extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%book_author_subscription}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['book_author_id', 'phone'], 'required'],
            ['book_author_id', 'integer'],
            ['phone', 'string', 'max' => 15],
            ['phone', 'match', 'pattern' => '/^\d+$/'],
            ['book_author_id', 'exist', 'targetClass' => BookAuthor::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(BookAuthor::class, ['id' => 'book_author_id']);
    }
}
