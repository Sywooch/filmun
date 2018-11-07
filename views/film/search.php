<?php
use yii\helpers\Html;
use app\models\Film;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models Film[] */
/* @var $term string */

$this->registerJs("
    $('.film-list').viewDetail();
");
?>

<h3 style="margin-top: 0">Результаты поиска &#171;<?= Html::encode($term) ?>&#187;</h3>

<hr>

<div id="film-list" class="clearfix">

    <?php foreach($models as $model): ?>
        <div class="film-list" data-key="<?= $model->id ?>">
            <?= $this->render('_view', ['model' => $model]) ?>
        </div>
    <?php endforeach; ?>

</div>
