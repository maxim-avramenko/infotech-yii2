<?php

/** @var yii\web\View $this */
/** @var app\models\BookAuthorForm $model */
/** @var app\models\BookAuthor $author */

use yii\bootstrap5\Html;

$this->title = 'Update author: ' . $author->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Authors', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $author->getFullName(), 'url' => ['view', 'id' => $author->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="book-author-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
