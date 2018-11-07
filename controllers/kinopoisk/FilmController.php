<?php
namespace app\controllers\kinopoisk;

use Yii;
use Zend\Http;
use yii\helpers\Html;
use yii\web\Controller;
use app\models\Film;
use app\models\KpCatalog;
use app\models\TopList;
use app\models\Proxy;
use app\models\SeriesEpisode;
use app\models\SeriesSeason;
use app\models\FilmTopList;
use app\components\KinopoiskParser;
use app\components\KinopoiskEpisodesParser;
use app\components\KinopoiskTopListIterator;

class FilmController extends Controller
{
    public static function headers()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, sdch, br',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,be;q=0.2,mk;q=0.2,uk;q=0.2',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        ];
    }

    public function actionTest()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $url = 'https://www.kinopoisk.ru/film/propovednik-2016-461353/';

        $client = new Http\Client($url);

        $client->setHeaders(array_merge(self::headers(), [
            'Referer' => 'https://www.google.com.ua/',
        ]));
        //Proxy::rand()->apply($client);
        $response = $client->send();
        echo $response->getBody();
        exit;
    }

    public function actionUpdateSeries()
    {
        set_time_limit(0);
        $query = Film::find()
            ->andWhere(['is_series' => 1])
            ->andWhere([
                'or',
                ['>', 'last_episode_at', strtotime("-3 years")],
                ['>', 'year', date('Y') -3],
            ])
            ->orderBy(['new_check_at' => SORT_ASC])
            ->limit(20);
        /** @var Film $film */
        foreach($query->each() as $i => $film) {
            $this->updateEpisodes($film);

            $parser = new KinopoiskParser($film->url);
            $parser->cache = false;
            $film->importFromParser($parser);

            $film->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + 3600*24*31
            ]);

            echo $film->id . ' ';
            ob_flush();
            flush();

            exit;
        }
    }

    public function getHtml($url)
    {
        for($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setHeaders(array_merge(KinopoiskParser::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                Proxy::rand()->apply($client);
                $response = $client->send();
                break;
            } catch(\Exception $e) {}
        }
        return $response->getBody();
    }

    public function actionEpisodes()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $query = Film::find()->andWhere('is_series = 1')->andWhere('id=7101');
        /** @var Film $filmModel */
        foreach($query->each() as $i => $filmModel) {
            $this->updateEpisodes($filmModel);
            echo '. ';
            ob_flush();
            flush();
        }
    }

    protected function updateEpisodes(Film $filmModel)
    {
        $parser = new KinopoiskEpisodesParser($filmModel->url);
        $parser->cache = false;
        $seasons = $parser->getSeasons();
        foreach($seasons as $season) {
            $seasonModel = SeriesSeason::find()->andWhere(['film_id' => $filmModel->id, 'number' => $season['number']])->one();
            if($seasonModel == null) {
                $seasonModel = new SeriesSeason;
                $seasonModel->film_id = $filmModel->id;
                $seasonModel->number = $season['number'];
            }
            $seasonModel->name = $season['name'];
            $seasonModel->count_episodes = $season['count_episodes'];
            $seasonModel->year = $season['year'];
            $seasonModel->save(false);

            foreach($season['episodes'] as $episode) {
                $episodeModel = SeriesEpisode::find()->andWhere(['film_id' => $filmModel->id, 'season_id' => $seasonModel->id, 'number' => $episode['number']])->one();
                if($episodeModel == null) {
                    $episodeModel = new SeriesEpisode;
                    $episodeModel->film_id = $filmModel->id;
                    $episodeModel->season_id = $seasonModel->id;
                    $episodeModel->number = $episode['number'];
                }
                $episodeModel->name = $episode['name'];
                $episodeModel->original_name = $episode['original_name'];
                $episodeModel->premiere = $episode['premiere'];
                $episodeModel->save(false);
            }
        }
    }

    public function actionResave()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $query = Film::find()->orderBy(['premiere' => SORT_DESC]);
        /** @var Film $film */
        foreach($query->each() as $i => $film) {
            if($i%100 == 0) {
                echo $i . ' ';
            }

            $parser = new KinopoiskParser($film->url);

            $film->setAttributes($parser->getAttributes());
            $film->save();
            if($film->hasErrors()) {
                dump($film->getFirstErrors());
            }

            $parser->clearContent();

            if($i%10 == 0) {
                echo '. ';
            }
            ob_flush();
            flush();
        }

        echo 'success';
    }


    public function actionCheck()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $catalogs = KpCatalog::find()->all();
        /** @var KpCatalog $catalog */
        foreach($catalogs as $catalog) {
            $catalog->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + $catalog->check_interval * 3600 * 24
            ]);

            $parsers = $catalog->getParsers();

            echo $catalog->id . ' - ' . $catalog->name . "<br>";
            ob_flush();
            flush();
            /** @var KinopoiskParser $parser */
            foreach($parsers as $parser) {
                $exists = Film::find()->andWhere(['kp_internal_id' => $parser->getInternalId()])->exists();
                if($exists) {
                    echo '<span style="color: lightgrey">*</span> ';
                    ob_flush();
                    flush();
                    continue;
                } else {
                    $model = new Film;
                    if($model->importFromParser($parser)) {
                        echo '<span style="color: white;background: green">+</span> ';
                    } else {
                        $errors = $model->getFirstErrors();
                        echo Html::a('#', $parser->url, ['style' => 'color: white;background: red', 'title' => current($errors)]) . ' ';
                    }
                }
                $parser->clearContent();

                ob_flush();
                flush();
            }
            echo '<br>';
            ob_flush();
            flush();
        }
    }

    public function actionTopList()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $topLists = TopList::find()->all();
        /** @var TopList $topList */
        foreach($topLists as $topList) {
            echo '<br>';
            echo $topList->id . ' => ' . $topList->name;
            echo '<br>';
            ob_flush();
            flush();

            $parsers = new KinopoiskTopListIterator($topList->kp_url . 'perpage/200/');
            /** @var KinopoiskParser $parser */
            foreach($parsers as $parser) {
                $model = Film::find()->andWhere(['url' => $parser->url])->one();
                if($model === null) {
                    echo '[' . $parser->url . '] ';
                    $model = $this->createModel($parser);
                }
                if($model === null) {
                    continue;
                }

                FilmTopList::create($model->id, $topList->id);

                $parser->clearContent();

                \phpQuery::$documents = [];
                \phpQuery::$defaultDocumentID = null;

                echo '. ';
                ob_flush();
                flush();
            }
        }
    }

    public function actionView($url)
    {
        $parser = new KinopoiskParser($url);
        $parser->cache = false;
        dump($parser->getAttributes());
    }

    public function actionCheckNew()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        /** @var KpCatalog $catalog */
        $catalog = KpCatalog::findOne(7);
        $parsers = $catalog->getParsers();
        foreach($parsers as $parser) {
            $exists = Film::find()->andWhere(['url' => $parser->url])->exists();
            if($exists) {
                echo '# ';
                ob_flush();
                flush();
                continue;
            }
            $this->createModel($parser);

            echo '. ';
            ob_flush();
            flush();
        }
    }
}