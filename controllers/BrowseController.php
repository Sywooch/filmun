<?php
namespace app\controllers;

use app\models\Film;
use Yii;
use app\models\FilmBrowse;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

class BrowseController extends Controller
{
    public function actionTable()
    {
        $query = FilmBrowse::find()
            ->with(['film', 'torrent'])
            ->andWhere(['user_id' => user()->id])
            ->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->setPagination([
            'defaultPageSize' => 50
        ]);

        return $this->render('table', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionIndex()
    {
        $data = [];
        $data['Сегодня'] = $this->getModels(strtotime('midnight'));
        $data['Вчера'] = $this->getModels(strtotime('midnight -1 day'));
        for($i = 2; $i <= 30; $i++) {
            $time = strtotime("midnight -{$i} day");
            $label = Yii::$app->formatter->asDatetime($time, 'dd MMMM');
            $data[$label] = $this->getModels($time);
        }
        return $this->render('index', [
            'data' => $data
        ]);
    }

    protected function getModels($from)
    {
        return Film::find()
            ->from(['t' => Film::tableName()])
            ->leftJoin('{{%film_browse}} fb', 'fb.film_id = t.id')
            ->andWhere(['fb.user_id' => user()->id])
            ->andWhere(['>=', 'fb.created_at', $from])
            ->andWhere(['<', 'fb.created_at', $from + 3600*24])
            ->orderBy(['fb.created_at' => SORT_DESC])
            ->groupBy('t.id')
            ->all();

        /*return FilmBrowse::find()
            ->with(['film', 'torrent'])
            ->andWhere(['user_id' => user()->id])
            ->andWhere(['>=', 'created_at', $from])
            ->andWhere(['<', 'created_at', $from + 3600*24])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();*/
    }
}