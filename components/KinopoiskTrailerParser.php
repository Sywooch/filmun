<?php
namespace app\components;

use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Zend\Http;

class KinopoiskTrailerParser extends Object
{
    protected $url;

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

    public function getContent()
    {
        $client = new Http\Client($this->url);
        $client->setHeaders(array_merge(KinopoiskParser::headers(), [
            'Referer' => 'https://www.google.com.ua/',
        ]));
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');
        //Proxy::rand()->apply($client);
        $response = $client->send();

        preg_match('#storageDirectory&quot;:&quot;(.+?)&quot;#', $response->getBody(), $matches);

        $client->resetParameters();
        $client->setUri("https://static.video.yandex.net/get/kinopoisk-trailers/{$matches[1]}/0h.xml");
        $client->setParameterGet(['nc' => microtime()]);
        $client->setHeaders(array_merge(KinopoiskParser::headers(), [
            'Referer' => $this->url,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]));

        $response = $client->send();
        $json = Json::decode($response->getBody());

        $items = array_filter($json['video-files']['items'], function($item){
            return $item['format'] == 'mp4';
        });

        $items = ArrayHelper::index($items, 'quality');

        $item = null;
        foreach(['720p', '480p', '360p', '346p', '240p'] as $quality) {
            if(array_key_exists($quality, $items)) {
                $item = $items[$quality];
                break;
            }
        }
        if($item === null) {
            $item = current($items);
        }

        $videoUrl = null;
        if(array_key_exists('get-url', $item)) {
            $videoUrl = $item['get-url'];
        } else if(array_key_exists('get-location-url', $item)) {
            $client->resetParameters();
            $client->setUri($item['get-location-url']);
            $client->setHeaders(array_merge(KinopoiskParser::headers(), [
                'Referer' => $this->url,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]));

            $response = $client->send();
            $json = Json::decode($response->getBody());

            $videoUrl = $json['video-location'];
        }

        $client->resetParameters();
        $client->setUri($videoUrl);
        $response = $client->send();

        return $response->getBody();
    }

}