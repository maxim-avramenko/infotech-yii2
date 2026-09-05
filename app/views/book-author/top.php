<?php

/** @var yii\web\View $this */
/** @var int $year */
/** @var int[] $years */
/** @var list<array{id: int|string, lastname: string, firstname: string, secondname: string|null, book_count: int|string}> $authors */

use yii\bootstrap5\Html;

$this->title = 'Top 10 authors';
$this->params['breadcrumbs'][] = $this->title;

$yearOptions = [];
foreach ($years as $availableYear) {
    $yearOptions[$availableYear] = (string) $availableYear;
}
?>
<div class="book-author-top">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Authors with the most books published in the selected year.</p>

    <?= Html::beginForm(['top'], 'get', ['class' => 'row g-2 align-items-end mb-4']) ?>
        <div class="col-auto">
            <label class="form-label" for="top-authors-year">Year</label>
            <?= Html::dropDownList('year', $year, $yearOptions, [
                'id' => 'top-authors-year',
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-auto">
            <?= Html::submitButton('Show', ['class' => 'btn btn-primary']) ?>
        </div>
    <?= Html::endForm() ?>

    <?php if ($authors === []): ?>
        <p>No authors published books in <?= Html::encode((string) $year) ?>.</p>
    <?php else: ?>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Author</th>
                    <th scope="col" class="text-end">Books in <?= Html::encode((string) $year) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $index => $author): ?>
                    <?php
                    $fullName = trim(implode(' ', array_filter([
                        $author['lastname'],
                        $author['firstname'],
                        $author['secondname'],
                    ])));
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <?= Html::a(
                                Html::encode($fullName),
                                ['view', 'id' => $author['id']],
                            ) ?>
                        </td>
                        <td class="text-end"><?= Html::encode((string) $author['book_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
