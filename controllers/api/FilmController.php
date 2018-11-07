<?php
namespace app\controllers\api;

use app\components\KinopoiskNewTrailerParser;
use app\components\KinopoiskTrailerParser;
use app\components\NnmClubParser;
use app\components\RutrackerParser;
use app\helpers\Translit;
use app\models\FilmMark;
use app\models\FilmWanted;
use app\models\SeriesSeason;
use app\models\Torrent;
use Yii;
use app\models\Film;
use app\models\search\FilmApiSearch;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class FilmController extends ApiController
{
    public function actionIndex($is_series = 0)
    {
        $searchModel = new FilmApiSearch();
        $searchModel->load($_GET);
        $searchModel->is_series = $is_series;
        $searchModel->status = Film::STATUS_CAME_OUT;
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        return $dataProvider;
    }

    public function actionSetWanted($id)
    {
        if(user()->isGuest) {
            return [];
        }
        if(Yii::$app->request->isPut) {
            FilmWanted::create($id, user()->id);
        }
        if(Yii::$app->request->isDelete) {
            FilmWanted::deleteAll(['film_id' => $id, 'user_id' => user()->id]);
        }
        return ['success' => true];
    }

    public function actionSetMark($id)
    {
        if(user()->isGuest) {
            return [];
        }
        if(Yii::$app->request->isPut) {
            $mark = $this->getRequestParam('mark');
            FilmMark::create($id, user()->id, $mark);
        }
        if(Yii::$app->request->isDelete) {
            FilmMark::deleteAll(['film_id' => $id, 'user_id' => user()->id]);
        }
        return ['success' => true];
    }

    public function actionRated()
    {
        if(user()->isGuest) {
            return [];
        }
        $rows = (new Query)
            ->from('{{%film_mark}} fm')
            ->join('JOIN', '{{%film}} f', 'f.id = fm.film_id')
            ->andWhere(['fm.user_id' => user()->id])
            ->indexBy('id')
            ->select([
                'id' => 'fm.film_id',
                'mark' => 'fm.mark',
                'name' => 'f.name',
                'year' => 'f.year',
                'director_ids' => 'f.director_ids'
            ])
            ->all();
        return array_map(function($row) {
            $director_ids = explode(',', $row['director_ids']);
            $row['director_ids'] = [];
            foreach($director_ids as $director_id) {
                $row['director_ids'][] = (int)$director_id;
            }
            return $row;
        }, $rows);
    }

    public function actionWanted()
    {
        if(user()->isGuest) {
            return [];
        }
        $film_ids = FilmWanted::find()->select(['film_id'])->andWhere(['user_id' => user()->id])->column();
        return Film::find()->andWhere(['id' => $film_ids])->all();
    }

    public function actionSearch($term, $limit = 10)
    {
        $search = trim($term);
        $search = mb_strtolower($search, 'utf-8');
        $search = Translit::t($search);
        $search = metaphone($search);

        $models = Film::find()
            ->with(['genres', 'countries'])
            ->andWhere(['like', 'search_text', $search])
            ->orderBy(['name' => SORT_ASC])
            ->limit(150)
            ->all();

        usort($models, function($m1, $m2) use($term){
            return (levenshtein($term, $m1->name) < levenshtein($term, $m2->name)) ? -1 : 1;
        });

        $models = array_slice($models, 0, $limit);
        return array_map(function(Film $model){
            return $model->toArray([], ['genres', 'countries']);
        }, $models);
    }

    public function actionView($id)
    {
        return Film::findOne($id);
    }

    public function actionSeasons($id)
    {
        $model = $this->findModel($id);

        return array_map(function(SeriesSeason $season){
            return $season->toArray([], ['episodes']);
        }, $model->seasons);
    }

    public function actionTorrents($id)
    {
        $model = $this->findModel($id);

        $torrent_ids = $model->getTorrentIds();

        Torrent::updateAll(['seeders' => null], [
            'and',
            ['id' => $torrent_ids],
            []
            //['<', 'seeders_check_at', time() - 3600]
        ]);

        $torrents = Torrent::find()
            ->andWhere(['id' => $torrent_ids])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return array_map(function(Torrent $torrent){
            return $torrent->toArray();
        }, $torrents);
    }

    public function actionCheckSeeders($torrent_id)
    {
        /** @var Torrent $model */
        $model = Torrent::findOne($torrent_id);
        $parser = $model->getParser();

        try {
            return $parser->getSeeders();
        } catch (\Exception $e) {
            return '0';
        }
    }

    public function actionMassCheckSeeders($term)
    {
        $urls = [];

        $data = NnmClubParser::checkSeeds($term);
        $urls = array_merge($urls, array_keys($data));
        foreach($data as $url => $seeders) {
            Torrent::updateSeeders($seeders, $url);
        }
        $data = RutrackerParser::checkSeeds($term);
        $urls = array_merge($urls, array_keys($data));
        foreach($data as $url => $seeders) {
            Torrent::updateSeeders($seeders, $url);
        }

        $torrents = Torrent::find()->andWhere(['url' => $urls])->select(['seeders', 'id'])->all();
        return array_map(function(Torrent $torrent){
            return [
                'id' => $torrent->id,
                'seeders' => $torrent->seeders
            ];
        }, $torrents);
    }

    public function actionTrailer($id)
    {
        Yii::$app->response->format = 'json';

        set_time_limit(0);
        ini_set("memory_limit", "512M");

        $files = glob(Yii::getAlias('@webroot/trailer/*.mp4'));
        usort($files, function($a, $b) {
            return filemtime($a) < filemtime($b);
        });
        $files = array_slice($files, 100);
        foreach ($files as $file) {
            unlink($file);
        }

        $model = $this->findModel($id);
        $path = Yii::getAlias('@webroot/trailer/' . $model->id . '.mp4');
        if(!file_exists($path) || true) {
            if($model->trailer_url) {
                $parser = new KinopoiskTrailerParser($model->trailer_url);
            } else {
                $parser = new KinopoiskNewTrailerParser($model->new_trailer_url);
            }
            $content = $parser->getContent();
            file_put_contents($path, $content);
        }

        $hostInfo = Yii::$app->urlManager->getHostInfo();

        return [
            'trailerUrl' => $hostInfo . Yii::getAlias('@web/trailer/' . $model->id . '.mp4')
        ];
    }

    /**
     * @param $id
     * @return Film
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Film::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не существует.');
        }
    }
}