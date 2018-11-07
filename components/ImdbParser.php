<?php
namespace app\components;

use app\models\Proxy;
use Yii;
use Zend\Http;

class ImdbParser extends Parser
{
    protected $content;

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
        $client = new Http\Client($url);
        $client->setHeaders(array_merge(self::headers(), [
            'Referer' => 'https://www.google.com.ua/',
        ]));
        //Proxy::rand()->apply($client);
        $response = $client->send();
        return $response->getBody();
    }

    public function clearContent()
    {
        $this->content = null;
        \phpQuery::$documents = [];
        \phpQuery::$defaultDocumentID = null;
    }

    public function loadContent()
    {
        if($this->content !== null) {
            return null;
        }
        $this->content = $this->getHtml($this->url);

        \phpQuery::newDocumentHTML($this->content, 'utf-8');
    }

    /**
     * @return mixed|null
     */
    public function getMark()
    {
        $this->loadContent();

        $val = pq('.ratings_wrapper .ratingValue span[itemprop="ratingValue"]')->text();
        $val = str_replace(',', '.', $val);
        $val = floatval($val);
        return $val ? $val : null;
    }

    public function getCountVotes()
    {
        $this->loadContent();

        $val = pq('.ratings_wrapper span[itemprop="ratingCount"]')->text();
        $val = preg_replace('#[^0-9]#', '', $val);
        $val = intval($val);
        return $val;
    }

    public function getMetacriticScore()
    {
        $this->loadContent();

        $val = pq('.titleReviewBar .metacriticScore span')->text();
        $val = preg_replace('#[^0-9]#', '', $val);
        $val = intval($val);
        return $val;
    }
}