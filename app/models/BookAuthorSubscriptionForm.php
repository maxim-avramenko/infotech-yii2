<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

class BookAuthorSubscriptionForm extends Model
{
    public $bookAuthorId;
    public string $phone = '';

    public function rules(): array
    {
        return [
            [['bookAuthorId', 'phone'], 'required'],
            ['bookAuthorId', 'integer'],
            ['phone', 'string', 'max' => 32],
            [
                'bookAuthorId',
                'exist',
                'targetClass' => BookAuthor::class,
                'targetAttribute' => 'id',
                'filter' => static function ($query): void {
                    $query->includeDeleted();
                },
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'bookAuthorId' => 'Author',
            'phone' => 'Phone',
        ];
    }
}
