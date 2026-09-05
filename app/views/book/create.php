<?php

/** @var yii\web\View $this */
/** @var app\models\BookForm $model */
/** @var array<int, string> $authors */

use yii\bootstrap5\Html;

$this->title = 'Create book';
$this->params['breadcrumbs'][] = ['label' => 'Books', 'url' => ['/site/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'authors' => $authors,
    ]) ?>
</div>
