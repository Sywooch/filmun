<?php
namespace app\controllers\torrent;

use app\models\TorrentError;
use Yii;
use app\components\RutrackerParser;
use app\models\Film;
use app\models\Torrent;
use yii\helpers\Html;
use yii\web\Controller;
use app\models\TorrentCatalog;
use Zend\Http;

class RutrackerController extends Controller
{
    public function actionSeeds()
    {
        //$data = NnmClubParser::checkSeeds('Танцующая');
        $data = RutrackerParser::checkSeeds('Салют-7');
        dump($data);
    }

    public function actionResave()
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $query = Torrent::find()->andWhere(['tracker' => TorrentCatalog::TRACKER_RUTRACKER])
            ->andWhere(['id' => 352445]);

        dump($query->all());

        $i = 1;
        /** @var Torrent $model */
        foreach($query->each() as $model) {
            if($i%1000 === 0) {
                echo $i;
            }
            $i++;
            $parser = new RutrackerParser($model->url);
            $parser->setTitle($model->title);
            $model->updateAttributes([
                'season' => $parser->getSeason(),
                'episode' => $parser->getEpisode(),
            ]);
            echo '. ';
            ob_flush();
            flush();
        }
    }

    public function actionCache($url)
    {
        $parser = new RutrackerParser($url);
        $filePath = $parser->getCacheFile();
        if(file_exists($filePath)) {
            echo file_get_contents($filePath);
        } else {
            echo 'no file';
        }
    }

    public function actionAddCatalogs()
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $url = 'https://rutracker.org/forum/viewforum.php?f=2238';
        $client = new Http\Client($url);
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');
        $response = $client->send();
        $content = $response->getBody();
        $content = str_replace('<meta charset="windows-1251">', '<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">', $content);

        \phpQuery::newDocumentHTML($content, 'utf-8');

        foreach(pq('h4.forumlink a') as $a) {
            $catalog_url = 'https://rutracker.org/forum/' . pq($a)->attr('href');

            $this->addCatalog($catalog_url);
            ob_flush();
            flush();
        }

        $this->addCatalog($url);
    }

    public function addCatalog($url)
    {
        $model = TorrentCatalog::findOne(['url' => $url]);
        if($model === null) {
            $model = new TorrentCatalog;
        }

        $client = new Http\Client($url);
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');
        $client->setUri($url);
        $response = $client->send();
        $content = $response->getBody();
        $content = str_replace('<meta charset="windows-1251">', '<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">', $content);

        \phpQuery::newDocumentHTML($content, 'utf-8');

        $name = pq('.nav:eq(0)')->text();
        $name = trim($name);
        $name = preg_replace("#[\s \n]+#ui", ' ', $name);
        $name = str_replace('Главная » Кино, Видео и ТВ » ', '', $name);

        $model->url = $url;
        $model->name = $name;
        $model->iterator_class = 'app\components\RutrackerIterator';
        $model->status = 1;
        $model->save(false);

        $model->updateAttributes([
            'count_pages' => $model->getParsers()->getTotalPages(),
        ]);

        echo $model->name . ' [' . $model->count_pages . ']<br>';

        return true;
    }

    public function actionView($url)
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $parser = new RutrackerParser($url);
        dump($parser->getAttributes());
    }

    public function actionPages()
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $catalogs = TorrentCatalog::find()
            //->andWhere(['<', 'new_check_at', time()])
            //->orderBy(['new_check_at' => SORT_ASC])
            //->limit(10)
            ->all();
        /** @var TorrentCatalog $catalog */
        foreach($catalogs as $catalog) {
            $parsers = $catalog->getParsers();
            $catalog->updateAttributes([
                'count_pages' => $parsers->getTotalPages(),
            ]);
            echo $catalog->id . ' ';
            ob_flush();
            flush();
        }
    }

    public function actionCheckTorrent($catalog_id = null)
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $catalogs = TorrentCatalog::find()
            ->orderBy(['count_pages' => SORT_DESC])
            ->andFilterWhere(['id' => $catalog_id])
            ->andWhere(['tracker' => TorrentCatalog::TRACKER_RUTRACKER])
            ->all();
        /** @var TorrentCatalog $catalog */
        foreach($catalogs as $catalog) {
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

            /** @var RutrackerParser $parser */
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
                        TorrentError::create($parser->url, $catalog, $model->getFirstErrors());

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

        $query = TorrentError::find();
        foreach($query->each() as $torrentError) {
            $exists = Torrent::find()->andWhere(['url' => $torrentError->url])->exists();
            if($exists) {
                $torrentError->delete();
            }
        }
    }
}