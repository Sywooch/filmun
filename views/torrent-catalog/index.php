<?php
use yii\grid\GridView;
use yii\bootstrap\Html;
use app\models\TorrentCatalog;
use app\models\Torrent;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \app\models\search\TorrentCatalogSearch */
/* @var $torrentError \app\models\Torrent */


?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
            'attribute' => 'id',
        ],
        [
            'attribute' => 'name',
            'content' => function(TorrentCatalog $model){
                return $model->getParsingLink() . ' ' . $model->getViewLink();
            }
        ],
        [
            'attribute' => 'count_till_week',
        ],
        [
            'attribute' => 'count_till_month',
        ],
        [
            'attribute' => 'count_total',
        ],
        [
            'attribute' => 'count_errors',
            'content' => function(TorrentCatalog $model){
                return Html::a($model->count_errors, ['display-errors', 'id' => $model->id], ['target' => '_blank']);
            }
        ],
        [
            'attribute' => 'success_percent',
        ],
        [
            'attribute' => 'check_interval',
        ],
        [
            'attribute' => 'tracker',
            'value' => 'trackerName',
            'filter' => $searchModel->getTrackerList(),
        ],
        [
            'attribute' => 'is_series',
            'filter' => ['1' => 'Да', 0 => 'Нет'],
        ],
        'new_check_at:datetime',
        'last_check_at:datetime',
    ],
]); ?>
