<?php

/** @var yii\web\View $this */
/** @var app\models\BookAuthorForm $model */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
?>

<div class="row">
    <div class="col-lg-5">
        <?php $form = ActiveForm::begin([
            'id' => 'book-author-form',
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-form-label'],
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'invalid-feedback'],
            ],
        ]); ?>

        <?= $form->field($model, 'lastname')->textInput(['autofocus' => true]) ?>
        <?= $form->field($model, 'firstname')->textInput() ?>
        <?= $form->field($model, 'secondname')->textInput() ?>

        <div class="form-group">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
