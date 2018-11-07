<?php
use yii\grid\GridView;
use app\models\FilmBrowse;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="torrent-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'username',
            'name',
            'kp_login',
            'email',
            [
                'content' => function($model){
                    /** @var FilmBrowse $filmBrowse */
                    $filmBrowse = app\models\FilmBrowse::find()->andWhere(['user_id' => $model->id])->orderBy(['created_at' => SORT_DESC])->one();
                    return $filmBrowse ? $filmBrowse->film->getFullName() : ' - ';
                },
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
