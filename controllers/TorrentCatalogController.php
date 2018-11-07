<?php
namespace app\controllers;

use app\components\NnmClubParser;
use app\models\Film;
use app\models\Torrent;
use Yii;
use yii\data\ActiveDataProvider;
use app\models\TorrentCatalog;
use app\models\search\TorrentCatalogSearch;
use yii\helpers\Html;
use yii\web\Controller;
use yii\filters\AccessControl;

class TorrentCatalogController extends Controller
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
        $searchModel = new TorrentCatalogSearch();
        $searchModel->load($_GET);

        $dataProvider = $searchModel->search();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel
        ]);
    }

    public function actionCheckTorrent($id)
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $catalog = TorrentCatalog::findOne($id);
        /** @var TorrentCatalog $catalog */

        $existsCount = 0;
        $parsers = $catalog->getParsers();

        echo $catalog->id . ' - ' . $catalog->name . "<br>";
        /** @var NnmClubParser $parser */
        foreach($parsers as $parser) {
            ob_flush();
            flush();

            $parser->cache = false;
            if($existsCount > 600) {
                break;
            }
            $exists = Torrent::find()->andWhere(['url' => $parser->url])->exists();
            if($exists) {
                echo '* ';
                $existsCount++;
            } else {
                $model = new Torrent;
                $model->last_check_at = time();
                $model->catalog_id = $catalog->id;
                $model->tracker = $catalog->tracker;
                $model->is_series = $catalog->is_series;
                $model->importFromParser($parser);
                if($model->hasErrors()) {
                    $title = implode(' / ', $model->getFirstErrors());
                    echo Html::a('#', $parser->url, ['title' => $title, 'target' => '_blank']) . ' ';
                } else {
                    foreach($model->kp_internal_ids as $kp_internal_id) {
                        $film = Film::importFromKp($kp_internal_id);
                        if($film) {
                            $film->updateAttributes([
                                'last_torrent_at' => $model->created_at,
                                'max_quality' => $film->getTorrentQuality()
                            ]);
                        }
                    }
                    echo '. ';
                    $existsCount = 0;
                }
            }
            $parser->clearContent();
        }
    }

    public function actionDisplayErrors($id)
    {
        $model = TorrentCatalog::findOne($id);
        $errors = $model->getTorrentErrors()->select('message, COUNT(*) count')->groupBy('message')->asArray()->all();
        return $this->render('display-errors', [
            'errors' => $errors
        ]);
    }

    public function actionSeries()
    {
        set_time_limit(0);
        $query = TorrentCatalog::find();
        foreach($query->each() as $model) {
            Torrent::updateAll(['is_series' => $model->is_series], ['catalog_id' => $model->id]);

            echo '. ';

            ob_flush();
            flush();
        }
    }

    public function actionUpdateAll()
    {
        set_time_limit(0);

        $query = TorrentCatalog::find();
        /** @var TorrentCatalog $model */
        foreach($query->each() as $model) {
            $model->count_total = $model->getTorrents()->count();
            $model->count_till_week = $model->getTorrents()->andWhere(['>=', 'created_at', time() - 3600*24*7])->count();
            $model->count_till_month = $model->getTorrents()->andWhere(['>=', 'created_at', time() - 3600*24*31])->count();
            $model->count_errors = $model->getTorrentErrors()->count();
            if($model->count_total) {
                $model->success_percent = round($model->count_total / ($model->count_total + $model->count_errors) * 100);
            } else {
                $model->success_percent = 100;
            }
            $model->check_interval = $model->generateCheckInterval();
            $model->save(false);

            echo '. ';
            ob_flush();
            flush();
        }
    }

    public function actionCountPages()
    {
        set_time_limit(0);

        $query = TorrentCatalog::find();
        /** @var TorrentCatalog $model */
        foreach($query->each() as $model) {
            $model->updateAttributes([
                'count_pages' => $model->getIterator()->getTotalPages(),
            ]);
            echo $model->id . ' ';
            ob_flush();
            flush();
        }
    }
}