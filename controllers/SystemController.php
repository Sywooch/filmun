<?php
namespace app\controllers;

use app\models\Film;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class SystemController extends Controller
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
        set_time_limit(0);

        $query = Film::find()->andWhere(['is_series' => 1])->andWhere([
            'or',
            ['>', 'last_episode_at', strtotime("-2 years")],
            ['>', 'year', date('Y') -2],
        ]);

        echo 'Всего ' . $query->count() . ' | ';

        /** @var Film $film */
        foreach($query->each() as $i => $film) {
            if($i%100 == 0) {
                echo $i;
            }
            $film->updateAttributes(['last_episode_at' => $film->generateLastEpisodeAt()]);
            echo '. ';
            ob_flush();
            flush();
        }
    }
}