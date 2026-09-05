<?php

declare(strict_types=1);

namespace app\models;

use app\models\query\SoftDeleteQuery;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $lastname
 * @property string $firstname
 * @property string|null $secondname
 * @property string $created_at
 * @property int $created_by
 * @property string|null $deleted_at
 *
 * @property-read Book[] $books
 * @property-read User $createdBy
 */
class BookAuthor extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%book_author}}';
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
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['lastname', 'firstname', 'created_by'], 'required'],
            [['lastname', 'firstname', 'secondname'], 'string', 'max' => 255],
            ['created_by', 'integer'],
            ['created_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'lastname' => 'Last name',
            'firstname' => 'First name',
            'secondname' => 'Patronymic',
            'created_at' => 'Created at',
            'created_by' => 'Created by',
        ];
    }

    public function getFullName(): string
    {
        return trim(implode(' ', array_filter([$this->lastname, $this->firstname, $this->secondname])));
    }

    public function getBooks(): ActiveQuery
    {
        return $this->hasMany(Book::class, ['id' => 'book_id'])
            ->viaTable('{{%book_book_author}}', ['book_author_id' => 'id'])
            ->orderBy([
                Book::tableName() . '.[[year]]' => SORT_DESC,
                Book::tableName() . '.[[title]]' => SORT_ASC,
            ]);
    }

    public function getCreatedBy(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
