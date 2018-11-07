<?php
use yii\bootstrap\Html;
use yii\grid\GridView;
use app\models\TorrentCatalog;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Каталоги торрентов';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="torrent-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'name',
                'content' => function(TorrentCatalog $model){
                    return Html::a($model->name, $model->url, ['target' => '_blank']);
                },
            ],
            'count_pages',
            'check_interval',
            [
                'attribute' => 'new_check_at',
                'format' => 'datetime',
                'options' => [
                    'width' => '160px',
                ],
            ],
            [
                'attribute' => 'last_check_at',
                'format' => 'datetime',
                'options' => [
                    'width' => '160px',
                ],
            ],
        ],
    ]); ?>

</div>
