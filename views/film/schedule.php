<?php
use yii\helpers\Html;
use app\models\SeriesEpisode;

/* @var $this yii\web\View */
/* @var $models app\models\SeriesEpisode[] */

$this->title = 'Моё расписание сериалов';

$this->registerJsFile('@web/js/film-view.js', ['depends' => ['app\assets\AppAsset']]);
$this->registerJs("
    $('.film-table-item').viewDetail();
");

$nextTitleShow = false;
?>

<div class="row" id="film-list">
    <div class="col-md-7">
        <table class="table table-hover no-header">
            <colgroup>
                <col width="2%">
                <col>
                <col>
                <col>
            </colgroup>
            <?php foreach($models as $model): ?>
                <?php if($model->premiere > time() && $nextTitleShow == false): ?>
                    <tr style="background: #f1ffbb;">
                        <td ></td>
                        <td colspan="3">
                            <h3 style="margin: 0;">Скоро выходят</h3>
                        </td>
                    </tr>
                    <?php $nextTitleShow = true; endif; ?>
                <tr class="film-table-item" data-key="<?= $model->film_id ?>">
                    <td><?= Html::img($model->film->getImageUrl(), ['style' => 'width:45px;']) ?></td>
                    <td>
                        <h3 style="margin-top: 0"><?= $model->film->name ?></h3>
                        <p><?= $model->season->name ?> | Эпизод <?= $model->number ?></p>
                    </td>
                    <td></td>
                    <td style="width: 75px;"><?= $model->getPremiereDecorate() ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="col-md-5">
        <div id="detail-view" style="margin-left: 10px;"></div>
    </div>
</div>