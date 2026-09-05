<?php

/** @var yii\web\View $this */
/** @var app\models\BookForm $model */
/** @var app\models\Book $book */
/** @var array<int, string> $authors */

use yii\bootstrap5\Html;

$this->title = 'Update book: ' . $book->title;
$this->params['breadcrumbs'][] = ['label' => 'Books', 'url' => ['/site/index']];
$this->params['breadcrumbs'][] = ['label' => $book->title, 'url' => ['view', 'id' => $book->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="book-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'authors' => $authors,
        'book' => $book,
    ]) ?>
</div>
