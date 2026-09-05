<?php

/** @var yii\web\View $this */
/** @var app\models\BookAuthorForm $model */

use yii\bootstrap5\Html;

$this->title = 'Create author';
$this->params['breadcrumbs'][] = ['label' => 'Authors', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-author-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
