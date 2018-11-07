<?php
namespace app\components;

use Yii;
use Zend\Http;
use app\models\Proxy;

class KinopoiskEpisodesParser extends Parser
{
    protected $_data;

    protected $content;

    public $cache = true;

    public $url;

    public function getHtml($url)
    {
        for($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setHeaders(array_merge(self::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                Proxy::rand()->apply($client);
                $response = $client->send();
                break;
            } catch(\Exception $e) {}
        }
        return $response->getBody();
    }

    public function clearContent()
    {
        $this->content = null;
        \phpQuery::$documents = [];
        \phpQuery::$defaultDocumentID = null;
    }

    public function getCacheFile()
    {
        return Yii::getAlias('@storage/episodes/' . md5($this->url) . '.html');
    }

    public function clearCache()
    {
        $fileName = $this->getCacheFile();
        if(file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public function loadContent()
    {
        if($this->content !== null) {
            return null;
        }
        //$this->content = $this->getHtml($this->url . 'episodes/');

        $filePath = $this->getCacheFile();
        if(file_exists($filePath) && $this->cache) {
            $content = file_get_contents($filePath);
        } else {
            $content = $this->getHtml($this->url . 'episodes/');
            file_put_contents($filePath, $content);
        }
        $this->content = $content;

        \phpQuery::newDocumentHTML($this->content, 'utf-8');
    }

    public static function headers()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, sdch, br',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,be;q=0.2,mk;q=0.2,uk;q=0.2',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        ];
    }

    public function getSeasons()
    {
        $this->loadContent();

        $seasons = [];

        $seasonNumber = 1;
        foreach(pq('.news h1.moviename-big[style*=font-size:21px]') as $h1) {
            $season = [];

            $td = pq($h1)->parent();
            $table = pq($td)->parent()->parent();

            $season['name'] = pq($h1)->text();
            $season['number'] = $seasonNumber++;
            pq($h1)->remove();

            preg_match('#([0-9]{4})?.+?([0-9]+)#u',  pq($td)->text(), $matches);
            if($matches) {
                $season['year'] = $matches[1];
                $season['count_episodes'] = $matches[2];
            } else {
                $season['year'] = null;
                $season['count_episodes'] = null;
            }

            $episodeNumber = 1;
            $season['episodes'] = [];
            foreach(pq('h1.moviename-big', $table) as $name) {
                $tr = pq($name)->parent()->parent();
                $name = pq($name)->text();

                $episode = [];
                $episode['name'] = $name;
                $episode['original_name'] = pq('.episodesOriginalName', $tr)->text();
                $episode['premiere'] = $this->dateToTimestamp(pq('td:eq(1)', $tr)->text());
                $episode['number'] = $episodeNumber++;

                $season['episodes'][] = $episode;
            }
            $seasons[] = $season;
        }

        return $seasons;
    }

    protected function dateToTimestamp($date)
    {
        $date = trim($date);
        if(mb_strlen($date, 'utf-8') == 4) {
            return strtotime('01-01-' . $date);
        }
        $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',

            '01-01' => 'Январь', '01-02' => 'Февраль', '01-03' => 'Март', '01-04' => 'Апрель', '01-05' => 'Май', '01-06' => 'Июнь', '01-07' => 'Июль', '01-08' => 'Август',
            '01-09' => 'Сентябрь', '01-10' => 'Октябрь', '01-11' => 'Ноябрь', '01-12' => 'Декабрь',
        ];
        foreach($months as $key => $month) {
            $key = str_pad ($key, 2,"0",STR_PAD_LEFT);
            $date = str_replace($month, $key, $date);
        }
        $date = preg_replace('#[^0-9]#u', '-', $date);
        return strtotime($date);
    }
}