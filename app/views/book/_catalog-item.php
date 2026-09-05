<?php

/** @var yii\web\View $this */
/** @var app\models\Book $model */

use yii\bootstrap5\Html;

$coverUrl = $model->getCoverUrl(60);
?>
<div class="d-flex gap-3">
    <div class="flex-shrink-0">
        <?php if ($coverUrl !== null): ?>
            <?= Html::a(
                Html::img($coverUrl, [
                    'width' => 60,
                    'height' => 60,
                    'alt' => $model->title,
                    'class' => 'rounded border',
                ]),
                ['/book/view', 'id' => $model->id],
            ) ?>
        <?php endif; ?>
    </div>
    <div class="flex-grow-1">
        <h2 class="h4"><?= Html::a(Html::encode($model->title), ['/book/view', 'id' => $model->id]) ?></h2>
        <p class="mb-1"><strong>Year:</strong> <?= Html::encode((string) $model->year) ?></p>
        <p class="mb-1"><strong>ISBN:</strong> <?= Html::encode($model->isbn) ?></p>
        <p class="mb-1"><strong>Authors:</strong> <?= Html::encode($model->getAuthorNames()) ?></p>
        <?php if ($model->description !== null && $model->description !== ''): ?>
            <p class="mb-1"><?= Yii::$app->formatter->asNtext($model->description) ?></p>
        <?php endif; ?>
        <p class="mb-1 text-muted">
            Added <?= Html::encode($model->created_at) ?>
            by <?= Html::encode($model->createdBy?->email ?? '') ?>
            <?php if ($model->updated_at !== null): ?>
                · Updated <?= Html::encode($model->updated_at) ?>
            <?php endif; ?>
        </p>
    </div>
</div>
