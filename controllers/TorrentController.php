<?php
namespace app\controllers;

use app\components\NnmClubParser;
use app\components\RutrackerParser;
use app\models\FilmBrowse;
use app\models\TorrentCatalog;
use Yii;
use app\models\Film;
use app\models\Torrent;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Controller;
use app\models\search\TorrentSearch;
use yii\web\NotFoundHttpException;

class TorrentController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new TorrentSearch();
        $searchModel->load($_GET);
        $dataProvider = $searchModel->search();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionCatalog()
    {
        $query = TorrentCatalog::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $dataProvider->setPagination([
            'defaultPageSize' => 50
        ]);
        $dataProvider->setSort([
            'defaultOrder' => ['last_check_at' => SORT_ASC],
        ]);

        return $this->render('catalog', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionTest($term)
    {
        $startTime = time();

        set_time_limit(0);

        echo '1: ' . (time() - $startTime) . '<br>';
        $data = NnmClubParser::checkSeeds($term);
        echo '2: ' . (time() - $startTime) . '<br>';
        foreach($data as $url => $seeders) {
            Torrent::updateAll(['seeders' => $seeders], ['url' => $url]);
        }
        echo '3: ' . (time() - $startTime) . '<br>';
        $data = RutrackerParser::checkSeeds($term);
        echo '4: ' . (time() - $startTime) . '<br>';
        foreach($data as $url => $seeders) {
            Torrent::updateAll(['seeders' => $seeders], ['url' => $url]);
        }
        echo '5: ' . (time() - $startTime) . '<br>';
        echo '<br>Finish';
    }

    public function actionBetter($film_id)
    {
        Yii::$app->response->format = 'json';

        $key = 'torrent-better-' . $film_id;

        $json = Yii::$app->cache->get($key);
        if($json === false) {
            $film = Film::findOne($film_id);
            $film->checkSeeders();

            $film->updateAttributes([
                'first_torrent_at' => $film->getTorrents()->select('MIN(created_at)')->scalar(),
                'last_torrent_at' => $film->getTorrents()->select('MAX(created_at)')->scalar(),
                'max_quality' => $film->getTorrentQuality()
            ]);

            $torrent = $this->getBetter($film);
            if($torrent) {
                if($torrent->has_advert) {
                    $text = '<i class="fa fa-bug"></i> ' . $torrent->quality_text . ', ' . $torrent->getSizeDecorate();
                } else {
                    $text = $torrent->quality_text . ', ' . $torrent->getSizeDecorate();
                }

                $text = Html::tag('p', $text);
                if($torrent->transfer_text){
                    $text .= Html::tag('p', $torrent->transfer_text);
                }
                $json = [
                    'success' => true,
                    'data' => [
                        'id' => $torrent->id,
                        'text' => $text,
                        'count' => $film->getTorrents()->count(),
                        'max_quality' => $film->max_quality,
                    ],
                ];
            } else {
                $json['success'] = false;
            }

            Yii::$app->cache->set($key, $json, 60*60);
        }
        return $json;
    }

    protected function getBetter(Film $film)
    {
        if(user()->isGuest) {
            $size = 4096;
        } else {
            $user = identity();
            $size = $user->desired_film_size;

            /** @var Torrent|null $torrent */
            $torrent = Torrent::find()
                ->from(['t' => Torrent::tableName()])
                ->join('JOIN', '{{%film_browse}} fb', 'fb.torrent_id = t.id')
                ->andWhere(['fb.user_id' => $user->id])
                ->andWhere(['fb.film_id' => $film->id])
                ->orderBy(['fb.created_at' => SORT_DESC])
                ->one();
            if($torrent) {
                return $torrent;
            }
        }

        $torrent = $film->getTorrents()
            ->andWhere(['>=', 'seeders', 10])
            ->andWhere(['between', 'size', $size * 0.8, $size * 1.2])
            ->orderBy(['quality' => SORT_DESC, 'transfer' => SORT_DESC, 'size' => SORT_DESC])
            ->one();
        if($torrent) {
            return $torrent;
        }

        $torrent = $film->getTorrents()
            ->andWhere(['>=', 'seeders', 10])
            ->andWhere(['between', 'size', $size * 0.6, $size * 1.4])
            ->orderBy(['quality' => SORT_DESC, 'transfer' => SORT_DESC, 'size' => SORT_DESC])
            ->one();
        if($torrent) {
            return $torrent;
        }

        $torrent = $film->getTorrents()
            ->andWhere(['>=', 'seeders', 5])
            ->andWhere(['between', 'size', $size * 0.6, $size * 1.4])
            ->orderBy(['quality' => SORT_DESC, 'transfer' => SORT_DESC, 'size' => SORT_DESC])
            ->one();
        if($torrent) {
            return $torrent;
        }

        $torrent = $film->getTorrents()
            ->andWhere(['>=', 'seeders', 10])
            ->orderBy(['quality' => SORT_DESC, 'transfer' => SORT_DESC, 'size' => SORT_DESC])
            ->one();
        if($torrent) {
            return $torrent;
        }

        $torrent = $film->getTorrents()
            ->andWhere(['>=', 'seeders', 1])
            ->orderBy(['quality' => SORT_DESC, 'transfer' => SORT_DESC, 'size' => SORT_DESC])
            ->one();
        if($torrent) {
            return $torrent;
        }

        return $film->getTorrents()
            ->orderBy(['quality' => SORT_DESC, 'transfer' => SORT_DESC, 'size' => SORT_DESC])
            ->one();
    }

    public function actionDownload($id, $film_id)
    {
        set_time_limit(50);

        $torrent = $this->findModel($id);

        /** @var Film $film */
        $film = Film::findOne($film_id);
        $publicName = $film->name . ' (' . $film->year . ') / IMDb ' . $film->imdb_mark . '.torrent';

        FilmBrowse::create($film->id, $torrent->id, user()->id);

        $content = $torrent->downloadFile();
        Yii::$app->response->sendContentAsFile($content, $publicName);
    }

    public function actionCheckNew()
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $query = TorrentCatalog::find();
        /** @var TorrentCatalog $catalog */
        foreach($query->each() as $catalog) {
            $interval = $catalog->check_interval;

            $existsCount = 0;
            $parsers = $catalog->getParsers();
            $parsers->proxy = false;

            $catalog->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + $interval * 3600 + rand(-$interval * 1200, $interval * 1200),
                'count_pages' => $parsers->getTotalPages(),
            ]);

            echo $catalog->id . ' - ' . $catalog->name . ' [' . $parsers->getTotalPages() . "]<br>";
            ob_flush();
            flush();

            $i = 0;

            /** @var NnmClubParser $parser */
            foreach($parsers as $parser) {
                if($i%50 == 0) {
                    echo '<span style="background: #69c6ff;color:white">[' . ($i/50+1) . ']</span> ';
                }
                $i++;

                if($existsCount > 150) {
                    break;
                }
                $exists = Torrent::find()->andWhere(['url' => $parser->url])->exists();
                if($exists) {
                    echo '* ';
                    ob_flush();
                    flush();

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
                        ob_flush();
                        flush();
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
                        ob_flush();
                        flush();

                        $existsCount = 0;
                    }
                }
                $parser->clearContent();
            }

            $catalog->updateCache();

            echo "<br>";
        }
    }

    /**
     * @param $id
     * @return Torrent
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Torrent::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не существует.');
        }
    }
}