<?php
namespace app\controllers\api;

use Yii;
use app\models\Country;
use yii\data\ActiveDataProvider;

class CountryController extends ApiController
{
    public function actionIndex($term = null)
    {
        $query = Country::find();
        $query->andFilterWhere(['like', 'name', $term]);
        $query->orderBy(['rating' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSizeLimit' => 500,
                'defaultPageSize' => 500,
            ],
        ]);

        return $dataProvider;
    }
}