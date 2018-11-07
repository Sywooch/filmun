<?php
namespace app\components;

use app\models\Proxy;
use Yii;
use yii\base\Object;
use yii\helpers\Json;
use yii\helpers\StringHelper;
use Zend\Http;

class KinopoiskNewTrailerParser extends Object
{
    protected $url;

    protected $_client;

    /**
     * KinopoiskTrailerParser constructor.
     * @param string $url
     * @param array $config
     */
    public function __construct($url, array $config = [])
    {
        $this->url = $url;
        parent::__construct($config);
    }

    protected function getHtml($url)
    {
        $client = $this->getClient();
        $client->resetParameters();
        $client->setUri($url);
        $response = $client->send();

        return $response->getBody();
    }

    protected function getClient()
    {
        if($this->_client === null) {
            $client = new Http\Client();
            $client->setHeaders(array_merge(KinopoiskParser::headers(), [
                'Referer' => 'https://www.google.com.ua/',
            ]));
            $client->setAdapter('Zend\Http\Client\Adapter\Curl');
            //Proxy::rand()->apply($client);
            $this->_client = $client;
        }
        return $this->_client;
    }

    public function getContent()
    {
        $videoUrl = $this->getVideoUrl();

        return $this->getHtml($videoUrl);
    }

    public function getVideoUrl()
    {
        $trailer_url = $this->getTrailerUrl();

        $body = $this->getHtml($trailer_url);
        \phpQuery::newDocumentHTML($body, 'utf-8');

        $content = pq('#player')->attr('data-params');
        $json = Json::decode($content);

        return $json['html5']['mp4']['videoUrl'];
    }

    public function getTrailerUrl()
    {
        $body = $this->getHtml($this->url);

        $pieces = explode('/', $this->url);
        $trailer_id = end($pieces);

        \phpQuery::newDocumentHTML($body, 'utf-8');

        $content = pq('script[data-state]')->text();
        $content = urldecode($content);
        $json = Json::decode($content);

        $trailers = $json['models']['trailers'];
        $trailer = $trailers[$trailer_id];

        $trailer_url = $trailer['url'];
        if(StringHelper::startsWith($trailer_url, '//')) {
            $trailer_url = 'https:' . $trailer_url;
        }

        return $trailer_url;
    }
}