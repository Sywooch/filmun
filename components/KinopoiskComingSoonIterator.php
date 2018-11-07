<?php
namespace app\components;

use app\models\Proxy;
use Yii;
use Zend\Http;

class KinopoiskComingSoonIterator extends ParserIterator
{
    public function getHtml($url)
    {
        for($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                Proxy::rand()->apply($client);
                //$client->setOptions(['sslverifypeer' => false]);

                $response = $client->send();
                break;
            } catch(\Exception $e) {}
        }
        return $response->getBody();
    }

    /**
     * @return float|int
     */
    public function getTotalPages()
    {
        return 1;
    }

    /**
     * Fetches the next batch of data.
     * @return array the data fetched
     */
    protected function fetchData()
    {
        if($this->_page > $this->getTotalPages()) {
            return [];
        }
        $content = $this->getHtml($this->baseUrl);
        $data = [];

        \phpQuery::newDocumentHtml($content);
        foreach(pq('.coming_films .item') as $div) {
            $url = trim(pq('.name > a', $div)->attr('href'));
            if(empty($url)) {
                continue;
            }
            $url = str_replace('/level/1/film/', '/film/', $url);
            $data[] = new KinopoiskParser('https://www.kinopoisk.ru' . $url);
        }

        $this->_page++;

        return $data;
    }
}