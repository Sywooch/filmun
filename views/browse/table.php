<?php
use yii\bootstrap\Html;
use yii\grid\GridView;
use app\models\FilmBrowse;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Мои просмотры';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="torrent-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'content' => function(FilmBrowse $model){
                    return Html::a('<i class="fa fa-2x fa-youtube-play"></i>', ['torrent/download', 'id' => $model->torrent_id, 'film_id' => $model->film_id], [
                        'class' => 'open-magic-player'
                    ]);
                },
                'options' => ['style' => 'width:2%']
            ],
            [
                'attribute' => 'film_id',
                'content' => function(FilmBrowse $model){
                    return $model->film->getLink();
                },
            ],
            [
                'attribute' => 'torrent_id',
                'content' => function(FilmBrowse $model){
                    return $model->torrent->title . ' ' . Html::a('<i class="fa fa-share-square-o"></i>', $model->torrent->url, ['target' => '_blank']);
                },
            ],
            [
                'label' => 'Размер',
                'content' => function(FilmBrowse $model){
                    return $model->torrent->getSizeDecorate();
                },
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'options' => [
                    'width' => '160px',
                ],
            ],
        ],
    ]); ?>

</div>
