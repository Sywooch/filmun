<?php
namespace app\controllers\torrent;

use app\models\Proxy;
use app\models\TorrentError;
use Yii;
use yii\helpers\Html;
use Zend\Http;
use yii\web\Controller;
use app\models\Film;
use app\models\Torrent;
use app\components\NnmClubParser;
use app\components\KinopoiskParser;
use app\models\TorrentCatalog;

class NnmClubController extends Controller
{
    public function actionResave()
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $query = Torrent::find()->andWhere(['tracker' => TorrentCatalog::TRACKER_NNM_CLUB]);

        $i = 1;
        /** @var Torrent $model */
        foreach($query->each() as $model) {
            if($i%1000 === 0) {
                echo $i;
            }
            $i++;
            $parser = new NnmClubParser($model->url);
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

    public function actionCheckTorrent()
    {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit(0);

        $catalogs = TorrentCatalog::find()
            ->orderBy(['count_pages' => SORT_DESC])
            ->andWhere(['tracker' => TorrentCatalog::TRACKER_NNM_CLUB])
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

        $query = TorrentError::find();
        foreach($query->each() as $torrentError) {
            $exists = Torrent::find()->andWhere(['url' => $torrentError->url])->exists();
            if($exists) {
                $torrentError->delete();
            }
        }
    }

    public function actionName()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $query = Torrent::find()->andWhere('name IS NULL');
        /** @var Torrent $model */
        foreach($query->each() as $i => $model) {
            if($i%100 == 0) {
                echo $i . ' ';
            }

            $model->name = $this->getName($model->title);
            $model->original_name = $this->getOriginalName($model->title);
            //$model->year = $this->getYear($model->title);
            $model->save(false, ['name', 'original_name']);

            echo '. ';
            ob_flush();
            flush();
        }
    }

    public function actionValues()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $query = Torrent::find()->limit(5000)->orderBy('RAND()');
        /** @var Torrent $model */

        $values = [];
        foreach($query->each() as $i => $model) {
            if($i%100 == 0) {
                echo $i . ' ';
            }
            $parser = new NnmClubParser($model->url);

            $data = $parser->data();
            foreach($data as $key => $val) {
                @$values[$key]++;
            }

            $parser->clearContent();

            echo '. ';
            ob_flush();
            flush();
        }
        asort($values);
        $values = array_reverse($values);
        dump($values);
    }

    public function actionView($url)
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $parser = new NnmClubParser($url);
        $parser->loadContent();
        echo $parser->content;
        exit;
        //$parser->cache = false;

        dump($parser->getAttributes());
    }

    public function actionAttrs($url)
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $parser = new NnmClubParser($url);
        $parser->loadContent();
        $parser->cache = false;

        dump($parser->getAttributes());
    }

    public function actionklklk()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $catalogs = TorrentCatalog::find()->all();
        /** @var TorrentCatalog $catalog */
        foreach($catalogs as $catalog) {
            $parsers = $catalog->getParsers();

            echo $catalog->id . ' - ' . $catalog->name . "<br>";
            echo 'Страниц ' . $parsers->getTotalPages() . "<br>";
            $i = 0;
            /** @var NnmClubParser $parser */
            foreach($parsers as $parser) {
                $parser->cache = false;
                if($i%50 == 0) {
                    echo ($i/50) + 1 . ' ';
                }
                $exists = Torrent::find()->andWhere(['url' => $parser->url])->exists();
                if($exists) {
                    echo '* ';
                } else {
                    try {
                        $model = new Torrent;
                        $model->last_check_at = time();
                        $model->catalog_id = $catalog->id;
                        $model->importFromParser($parser);
                        if($model->importFromParser($parser)) {
                            echo '. ';
                        } else {
                            $errors = $model->getFirstErrors();
                            echo Html::a('#', $parser->url, ['style' => 'color:red', 'title' => current($errors)]) . ' ';
                        }
                    } catch(\Exception $e) {}
                }
                ob_flush();
                flush();
                $parser->clearContent();

                $i++;
            }
            echo '<br>';
            echo "\n";
        }
    }

    public function actionCreateCatalogs()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $links = [
            /*'http://nnm-club.me/forum/viewforum.php?f=216',
            'http://nnm-club.me/forum/viewforum.php?f=318',
            'http://nnm-club.me/forum/viewforum.php?f=224',
            'http://nnm-club.me/forum/viewforum.php?f=220',
            'http://nnm-club.me/forum/viewforum.php?f=254',
            'http://nnm-club.me/forum/viewforum.php?f=229',
            'http://nnm-club.me/forum/viewforum.php?f=1219',
            'http://nnm-club.me/forum/viewforum.php?f=768',
            'http://nnm-club.me/forum/viewforum.php?f=769',
            'http://nnm-club.me/forum/viewforum.php?f=620',
            'http://nnm-club.me/forum/viewforum.php?f=624',
            'http://nnm-club.me/forum/viewforum.php?f=628',*/
            //'http://nnm-club.me/forum/viewforum.php?f=713',
            'http://nnm-club.me/forum/viewforum.php?f=576',
        ];
        foreach($links as $link) {
            $client = new Http\Client($link);
            Proxy::rand()->apply($client);
            $response = $client->send();

            \phpQuery::newDocumentHTML($response->getBody(), 'utf-8');

            $mainTitle = trim(pq('.maintitle')->text());

            foreach(pq('.row1 h2.forumlink a.forumlink') as $a) {
                $url = 'http://nnm-club.me/forum/'.pq($a)->attr('href');

                $exists = TorrentCatalog::find()->andWhere(['url' => $url])->exists();
                if($exists) {
                    TorrentCatalog::updateAll([
                        'name' => $mainTitle . ' - ' . trim(pq($a)->text()),
                    ], ['url' => $url]);

                    echo Html::tag('div', pq($a)->text(), ['style' => 'color:red']);
                    continue;
                }

                $model = new TorrentCatalog();
                $model->name = $mainTitle . ' - ' . trim(pq($a)->text());
                $model->url = $url;
                $model->iterator_class = 'app\components\NnmClubIterator';
                $model->save(false);
                echo pq($a)->text();
                echo '<br>';
            }
        }
    }

}