<?php
namespace app\controllers\api;


use app\models\Person;
use yii\data\ActiveDataProvider;

class ActorController extends ApiController
{
    public function actionIndex($term = null)
    {
        $query = Person::find();
        $query->andFilterWhere(['like', 'name', $term]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        return $dataProvider;
    }

    public function actionView($id)
    {
        return Person::findOne($id);
    }
}