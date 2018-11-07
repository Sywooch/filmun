<?php
namespace app\components;

use Yii;
use Zend\Http;
use app\models\Proxy;

class KinopoiskPersonParser extends Parser
{
    protected $content;

    public $cache = true;

    public static function headers()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, sdch, br',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4,be;q=0.2,mk;q=0.2,uk;q=0.2',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        ];
    }

    public function getHtml($url)
    {
        for($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setHeaders(array_merge(self::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                //Proxy::rand()->apply($client);
                $response = $client->send();
                break;
            } catch(\Exception $e) {}
        }
        return $response->getBody();
    }

    public function getCacheFile()
    {
        return Yii::getAlias('@storage/kp-person/' . md5($this->url) . '.html');
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
        $this->content = $this->getHtml($this->url);

        /*$filePath = Yii::getAlias('@storage/kp-person/' . md5($this->url) . '.html');
        if(file_exists($filePath) && $this->cache) {
            $content = file_get_contents($filePath);
        } else {
            $content = $this->getHtml($this->url);
            file_put_contents($filePath, $content);
        }
        $this->content = $content;*/

        \phpQuery::newDocumentHTML($this->content, 'utf-8');
    }

    public function getName()
    {
        $this->loadContent();

        $name = pq('#headerPeople .moviename-big')->text();

        return trim($name);
    }

    public function getImageUrl()
    {
        $this->loadContent();
        $image_url = pq('#photoBlock img[itemprop="image"]')->attr('src');
        if($image_url == 'https://st.kp.yandex.net/images/persons/photo_none.png') {
            $image_url = null;
        }
        return $image_url;
    }

    public function getOriginalName()
    {
        $this->loadContent();

        $name = pq('#headerPeople [itemprop="alternateName"]')->text();
        return trim($name);
    }

    public function getFilmsParsers()
    {
        $this->loadContent();

        $parsers = [];
        foreach(pq('.personPageItems[data-work-type=director] .item .name a') as $a) {
            $uri = trim(pq($a)->attr('href'));
            if(substr($uri, 1, 3) == 'top') {
                continue;
            }
            $url = 'https://www.kinopoisk.ru' . $uri;
            $parser = new KinopoiskParser($url);
            $parser->cache = $this->cache;
            $parsers[] = $parser;
        }
        foreach(pq('.personPageItems[data-work-type=actor] .item .name a') as $a) {
            $uri = trim(pq($a)->attr('href'));
            if(substr($uri, 1, 3) == 'top') {
                continue;
            }
            $url = 'https://www.kinopoisk.ru' . $uri;
            $parser = new KinopoiskParser($url);
            $parser->cache = $this->cache;
            $parsers[] = $parser;
        }
        return $parsers;
    }

    public function clearContent()
    {
        $this->content = null;
        \phpQuery::$documents = [];
    }
}