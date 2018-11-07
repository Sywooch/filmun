<?php
use yii\helpers\Json;

/* @var $this yii\web\View */
/* @var $model app\models\Film */

$this->title = 'Мои просмотры';

$this->registerJsFile('@web/js/film-view.js', ['depends' => ['app\assets\AppAsset']]);
$this->registerJs("
    $('.film-list').viewDetail();
");
?>

<div id="film-list" class="clearfix">

    <?php foreach($data as $label => $models): ?>
        <?php if(empty($models)) continue; ?>

        <h1><?= $label ?></h1>

        <?php foreach($models as $model): ?>
            <div class="film-list" data-key="<?= $model->id ?>">
                <?= $this->render('//film/_view', ['model' => $model]) ?>
            </div>
        <?php endforeach; ?>

    <?php endforeach; ?>

</div>