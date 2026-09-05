<?php

/** @var yii\web\View $this */
/** @var app\models\BookForm $model */
/** @var array<int, string> $authors */
/** @var app\models\Book|null $book */
/** @var yii\bootstrap5\ActiveForm $form */

use app\assets\TomSelectAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

$book = $book ?? null;
TomSelectAsset::register($this);
$searchUrl = Url::to(['/book-author/search']);
$this->registerJs(
    'window.bookAuthorSearchUrl = ' . Json::htmlEncode($searchUrl) . ';',
    View::POS_HEAD,
);
$this->registerJsFile('@web/js/book-author-select.js', [
    'depends' => [TomSelectAsset::class],
]);
?>

<div class="row">
    <div class="col-lg-6">
        <?php $form = ActiveForm::begin([
            'id' => 'book-form',
            'options' => ['enctype' => 'multipart/form-data'],
            'enableClientValidation' => false,
            'enableAjaxValidation' => false,
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-form-label'],
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'invalid-feedback'],
            ],
        ]); ?>

        <?= $form->field($model, 'title')->textInput(['autofocus' => true]) ?>
        <?= $form->field($model, 'year')->textInput(['type' => 'number']) ?>
        <?= $form->field($model, 'isbn')->textInput() ?>
        <?= $form->field($model, 'authorIds')->dropDownList($authors, [
            'multiple' => true,
            'class' => 'form-select',
        ]) ?>
        <?= $form->field($model, 'description')->textarea(['rows' => 5]) ?>
        <?= $form->field($model, 'imageFile')->fileInput(['accept' => 'image/*']) ?>

        <?php if ($book?->getCoverUrl(60) !== null): ?>
            <p>
                <img src="<?= Html::encode($book->getCoverUrl(60)) ?>" alt="Cover" width="60" height="60">
            </p>
        <?php endif; ?>

        <div class="form-group">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
