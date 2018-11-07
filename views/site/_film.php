<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Film */
?>

<div class="film-short" onclick="viewFilm(<?= $model->id ?>)">
    <?php if($model->is_series): ?>
        <i class="fa fa-film"></i>
    <?php endif; ?>


    <?php if($model->imdb_mark): ?>
    <div class="short-film-mark">
        IMDb <?= $model->getIMDbMarkDecorate() ?>
    </div>
    <?php endif; ?>

    <?= Html::img($model->getImageUrl()) ?>

    <div class="film-title"><?= Html::encode($model->name) ?></div>

    <div class="film-desc">
        <?= $model->year ?> г.,
        <span style="color: grey;"><?= implode(', ', array_slice(ArrayHelper::getColumn($model->genres, 'name'), 0, 3)) ?></span>
    </div>

    <div class="film-directors">
        <?= $model->getDirectorsTag() ?>
    </div>
</div>