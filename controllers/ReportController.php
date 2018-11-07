<?php
namespace app\controllers;

use app\models\KinopoiskReport;
use app\models\TorrentReport;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class ReportController extends Controller
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
        $end_at = mktime(0, 0, 0) + 3600 * 24;

        $kinopoiskReport = new KinopoiskReport;
        $kinopoiskReport->start_at = $end_at - 3600 * 24 * 10;
        $kinopoiskReport->end_at = $end_at;

        $torrentReport = new TorrentReport;
        $torrentReport->start_at = $end_at - 3600 * 24 * 10;
        $torrentReport->end_at = $end_at;

        return $this->render('index', [
            'kinopoiskReport' => $kinopoiskReport,
            'torrentReport' => $torrentReport,
        ]);
    }

    public function actionImdb()
    {
        return $this->render('imdb', [
        ]);
    }
}