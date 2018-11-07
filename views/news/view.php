<?php
use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $model app\models\News */
?>

<h1><?= $model->title ?></h1>

<div style="max-width:800px;text-align: justify;">
    <?php if($model->kp_preview): ?>
        <?= Html::img($model->getImageUrl()) ?>
    <?php endif; ?>

    <?= $model->content ?>
</div>

<hr>

<?= Html::a('[kinopoisk.ru]', $model->kp_url, ['target' => '_blank']) ?>
