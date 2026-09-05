<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

class BookAuthorForm extends Model
{
    public string $lastname = '';
    public string $firstname = '';
    public string $secondname = '';

    public static function fromAuthor(BookAuthor $author): self
    {
        $form = new self();
        $form->lastname = $author->lastname;
        $form->firstname = $author->firstname;
        $form->secondname = $author->secondname ?? '';

        return $form;
    }

    public function rules(): array
    {
        return [
            [['lastname', 'firstname'], 'required'],
            [['lastname', 'firstname', 'secondname'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'lastname' => 'Last name',
            'firstname' => 'First name',
            'secondname' => 'Patronymic',
        ];
    }
}
