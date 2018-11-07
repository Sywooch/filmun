<?php
use yii\bootstrap\Html;
use yii\grid\GridView;
use app\models\Torrent;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\search\TorrentSearch */

$this->title = 'Торренты';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="torrent-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'title',
                'content' => function(Torrent $model){
                    $options = ['target' => '_blank'];
                    return Html::a($model->title, ['torrent/nnm-club/view', 'id' => $model->id], $options) .
                        ' ' . Html::a('<i class="fa fa-share-square-o"></i>', $model->url, $options);
                },
            ],
            [
                'attribute' => 'size_text',
                'value' => function(Torrent $model){ return StringHelper::truncate($model->size_text, 32); },
            ],
            [
                'attribute' => 'quality_text',
                'value' => function(Torrent $model){ return StringHelper::truncate($model->quality_text, 32); },
            ],
            [
                'attribute' => 'transfer_text',
                'value' => function(Torrent $model){ return StringHelper::truncate($model->transfer_text, 32); },
            ],
            [
                'attribute' => 'created_at',
                'format' => 'date',
                'options' => [
                    'width' => '120px',
                ],
            ],
            [
                'attribute' => 'last_check_at',
                'format' => 'datetime',
                'options' => [
                    'width' => '120px',
                ],
            ],
            [
                'attribute' => 'new_check_at',
                'format' => 'datetime',
                'options' => [
                    'width' => '120px',
                ],
            ],
        ],
    ]); ?>

</div>
