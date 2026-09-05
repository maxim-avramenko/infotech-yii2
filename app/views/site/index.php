<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\widgets\LinkPager;
use yii\widgets\ListView;

$this->title = 'Book catalog';
?>
<div class="site-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!Yii::$app->user->isGuest): ?>
        <p>
            <?= Html::a('Create book', ['/book/create'], ['class' => 'btn btn-success']) ?>
        </p>
    <?php endif; ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '@app/views/book/_catalog-item',
        'layout' => "{items}\n{pager}",
        'emptyText' => 'No books yet.',
        'options' => ['class' => 'book-catalog'],
        'itemOptions' => ['class' => 'book-catalog-item mb-4 pb-4 border-bottom'],
        'pager' => [
            'class' => LinkPager::class,
        ],
    ]) ?>
</div>
