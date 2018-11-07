<?php
namespace app\controllers\api;

use Yii;
use app\models\Person;
use app\models\PersonFavourite;
use yii\data\ActiveDataProvider;

class PersonController extends ApiController
{
    public function actionIndex($term = null)
    {
        $query = Person::find();
        $query->andFilterWhere(['like', 'name', $term]);
        $query->orderBy(['rating' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        return $dataProvider;
    }

    public function actionSetFavourite($id, $role)
    {
        if(user()->isGuest) {
            return [];
        }
        if(Yii::$app->request->isPut) {
            PersonFavourite::create($id, user()->id, $role);
        }
        if(Yii::$app->request->isDelete) {
            PersonFavourite::remove($id, user()->id, $role);
        }
        return ['success' => true];
    }

    public function actionFavourites()
    {
        if(user()->isGuest) {
            return [];
        }

        return PersonFavourite::find()
            ->with(['person'])
            ->andWhere(['user_id' => user()->id])
            ->all();
    }

    public function actionView($id)
    {
        return Person::findOne($id);
    }
}