<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

$this->title = 'Authors';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-author-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create author', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'lastname',
            'firstname',
            'secondname',
            'created_at',
            [
                'attribute' => 'created_by',
                'value' => static fn ($model) => $model->createdBy?->email,
            ],
            [
                'class' => ActionColumn::class,
            ],
        ],
    ]) ?>
</div>
