<?php
namespace app\controllers;

use app\components\KinopoiskNewTrailerParser;
use app\components\KinopoiskParser;
use app\components\NnmClubParser;
use app\models\Country;
use app\models\FilmCountry;
use app\models\Proxy;
use Yii;
use app\models\Currency;
use app\models\Film;
use app\models\Torrent;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Controller;
use app\models\TorrentCatalog;
use Zend\Http;

class ZtestController extends Controller
{
    public function actionFilmQuality()
    {
        $model = Film::findOne(33667);

        /** @var Torrent $torrent */
        echo '<table border="1">';
        foreach($model->getTorrents()->all() as $torrent) {
            echo '<tr>';
            echo Html::tag('td', $torrent->title);
            echo Html::tag('td', $torrent->quality_text);
            echo Html::tag('td', $torrent->quality);
            echo Html::tag('td', $torrent->size_text);
            echo Html::tag('td', $torrent->transfer_text);
            echo Html::tag('td', $torrent->getTransferDecorate());
            echo Html::tag('td', $torrent->transfer);
            echo '</tr>';
        }
        echo '</table>';
    }


    public function actionCheckKp()
    {
        $parser = new KinopoiskParser('https://www.kinopoisk.ru/film/251733/');
        $data = $parser->getAttributes();
        dump($data);
        exit;
    }

    public function actionResave()
    {
        /** @var Film $model */
        $models = Film::find()
            ->limit(100)
            ->orderBy(['id' => SORT_ASC])
            ->all();
        $i = 0;
        while(count($models)) {
            $last = end($models);
            foreach($models as $model) {

                $director_ids = $model->generateDirectorIds();
                $model->updateAttributes([
                    'director_ids' => implode(',', $director_ids)
                ]);

                if($i%100===0) {
                    echo $model->id.' ';
                } else {
                    echo '. ';
                }

                ob_flush();
                flush();
                $i++;
            }
            $models = Film::find()
                ->andWhere(['>', 'id', $last->id])
                ->limit(100)->orderBy(['id' => SORT_ASC])->all();
        }
    }

    public function actionNnmClub()
    {
        $parser = new NnmClubParser('http://nnm-club.me/forum/viewtopic.php?t=1160608');

        dump($parser->getAttributes());
    }

    public function actionIndexzzz()
    {
        $uri = 'https://besplatka.ua/obyavlenie/2--h-komnatnaya-3-st--lyustdorfskoi-dorogi-6f7b5e';
        $options = ['timeout' => 20,'sslverifypeer' => false,];
        $c = new Http\Client($uri, $options);

        /* proxy */
        $adapter = new Http\Client\Adapter\Curl();
        /*$options = [
            'proxyhost' => '104.227.102.72',
            'proxyport' => 9662,
            'proxy_user' => 'xkzA54',
            'proxy_pass' => 'PF34TY',
        ];
        $adapter->setOptions($options);*/
        $c->setAdapter($adapter);
        /* end proxy */

        $r = $c->send();
        var_dump($r->getStatusCode());


        $c->resetParameters();
        $c->setUri('https://besplatka.ua/message/show-phone');
        $c->setParameterPost(['id' => 22500631]);
        $c->setHeaders([
            ':authority' => 'besplatka.ua',
            ':method' => 'POST',
            ':path' => '/message/show-phone',
            ':scheme' => 'https',
            'accept' => '*/*',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'ru,en-US;q=0.9,en;q=0.8,uk;q=0.7',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'origin' => 'https://besplatka.ua',
            'referer' => $uri,
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/65.0.3325.181 Chrome/65.0.3325.181 Safari/537.36',
            'x-Requested-With' => 'XMLHttpRequest',
        ]);

        $r2 = $c->send();
        var_dump($r2->getStatusCode());
    }

    public function actionParse()
    {
        set_time_limit(0);

        $query = Film::find()
            ->andWhere(['>', 'premiere', time() - 3600*24*31*36])
            ->andWhere(['<', 'premiere', time() + 3600*24*31*12])
        ;

        echo '['.$query->count().']';

        /** @var Film $model */
        foreach($query->each() as $i => $model) {
            $parser = new KinopoiskParser($model->url);
            //parser->cache = false;
            $new_trailer_url = $parser->getNewTrailerUrl();
            $model->updateAttributes(['new_trailer_url' => $new_trailer_url]);

            if($new_trailer_url) {
                echo '<span style="background: green;color: white">+</span> ';
            } else {
                if($i%10 == 0) {
                    echo $i.' ';
                }
            }
            ob_flush();
            flush();
        }
    }

}