<?php
namespace app\controllers;

use app\models\FilmPerson;
use app\models\Person;
use Yii;
use yii\web\Controller;
use app\models\search\DirectorSearch;
use yii\filters\AccessControl;

class DirectorController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new DirectorSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        $this->view->title = 'Режиссеры';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }
}