<?php

/** @var yii\web\View $this */
/** @var app\models\Book $model */

use app\models\User;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\bootstrap5\Html;
use yii\widgets\DetailView;

BootstrapPluginAsset::register($this);
$this->registerJsFile('@web/js/author-subscribe.js', [
    'depends' => [BootstrapPluginAsset::class],
]);

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Books', 'url' => ['/site/index']];
$this->params['breadcrumbs'][] = $this->title;

$identity = Yii::$app->user->identity;
$phone = $identity instanceof User ? $identity->phone : '';

$authorButtons = [];
foreach ($model->authors as $author) {
    $authorButtons[] = Html::button($author->getFullName(), [
        'type' => 'button',
        'class' => 'btn btn-link p-0 align-baseline',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#author-subscribe-modal',
        'data-author-id' => (string) $author->id,
        'data-author-name' => $author->getFullName(),
    ]);
}
?>
<div class="book-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (!Yii::$app->user->isGuest): ?>
        <p>
            <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Are you sure you want to delete this book?',
                    'method' => 'post',
                ],
            ]) ?>
        </p>
    <?php endif; ?>

    <?php if ($model->getCoverUrl(600) !== null): ?>
        <p>
            <img src="<?= Html::encode($model->getCoverUrl(600)) ?>" alt="<?= Html::encode($model->title) ?>" width="600" height="600">
        </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'title',
            'year',
            'isbn',
            'description:ntext',
            [
                'label' => 'Authors',
                'format' => 'raw',
                'value' => $authorButtons === [] ? '' : implode(', ', $authorButtons),
            ],
            'created_at',
            'updated_at',
            [
                'attribute' => 'created_by',
                'value' => $model->createdBy?->email,
            ],
        ],
    ]) ?>
</div>

<div class="modal fade" id="author-subscribe-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= Html::beginForm(['/book-author/subscribe'], 'post') ?>
            <?= Html::hiddenInput('bookId', $model->id) ?>
            <?= Html::hiddenInput('BookAuthorSubscriptionForm[bookAuthorId]', '', [
                'id' => 'subscription-author-id',
            ]) ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5 js-author-name"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="subscription-phone">Phone</label>
                    <?= Html::input('tel', 'BookAuthorSubscriptionForm[phone]', $phone, [
                        'id' => 'subscription-phone',
                        'class' => 'form-control',
                        'required' => true,
                        'placeholder' => '+7 900 111-22-33',
                    ]) ?>
                </div>
            </div>
            <div class="modal-footer">
                <?= Html::submitButton('Подписаться на новые поступления автора', [
                    'class' => 'btn btn-primary',
                ]) ?>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>
