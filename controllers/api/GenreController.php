<?php
namespace app\controllers\api;

use Yii;
use app\models\Genre;
use yii\data\ActiveDataProvider;

class GenreController extends ApiController
{
    public function actionIndex($term = null)
    {
        $query = Genre::find();
        $query->andFilterWhere(['like', 'name', $term]);
        $query->orderBy(['rating' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSizeLimit' => 100,
                'defaultPageSize' => 100,
            ],
        ]);

        return $dataProvider;
    }
}