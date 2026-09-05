<?php

/** @var yii\web\View $this */
/** @var app\models\BookAuthor $model */
/** @var yii\data\ActiveDataProvider $books */

use yii\bootstrap5\Html;
use yii\widgets\DetailView;
use yii\widgets\LinkPager;
use yii\widgets\ListView;

$this->title = $model->getFullName();
$this->params['breadcrumbs'][] = Yii::$app->user->isGuest
    ? ['label' => 'Top authors', 'url' => ['top']]
    : ['label' => 'Authors', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-author-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!Yii::$app->user->isGuest): ?>
        <p>
            <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Are you sure you want to delete this author?',
                    'method' => 'post',
                ],
            ]) ?>
        </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'lastname',
            'firstname',
            'secondname',
            'created_at',
            [
                'attribute' => 'created_by',
                'value' => $model->createdBy?->email,
                'visible' => !Yii::$app->user->isGuest,
            ],
        ],
    ]) ?>

    <h2 class="h3 mt-4">Books</h2>
    <?= ListView::widget([
        'dataProvider' => $books,
        'itemView' => '@app/views/book/_catalog-item',
        'layout' => "{items}\n{pager}",
        'emptyText' => 'This author has no books yet.',
        'options' => ['class' => 'book-catalog'],
        'itemOptions' => ['class' => 'book-catalog-item mb-4 pb-4 border-bottom'],
        'pager' => [
            'class' => LinkPager::class,
        ],
    ]) ?>
</div>
