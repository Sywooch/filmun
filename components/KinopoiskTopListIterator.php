<?php
namespace app\components;

use app\models\Proxy;
use Yii;
use yii\helpers\ArrayHelper;
use Zend\Http;

class KinopoiskTopListIterator extends ParserIterator
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
        if($this->_totalPages === null) {
            $html = $this->getHtml($this->baseUrl);
            \phpQuery::newDocumentHTML($html);
            $url = pq('.navigator .list li.arr:last-child a')->attr('href');

            preg_match('#page/([0-9]+)/#', $url, $matches);

            $this->_totalPages = ArrayHelper::getValue($matches, 1, 1);
        }
        return $this->_totalPages;
    }

    /**
     * Fetches the next batch of data.
     * @return array the data fetched
     */
    protected function fetchData()
    {
        $page = $this->_page;
        $this->_page++;

        if($page > $this->getTotalPages()) {
            return [];
        }
        $pageUrl = trim($this->baseUrl, '/') . '/page/' . $page . '/';

        $content = $this->getHtml($pageUrl);
        $data = [];

        \phpQuery::newDocumentHtml($content);
        foreach(pq('#itemList tr') as $tr) {
            $url = trim(pq('.poster > a', $tr)->attr('href'));
            if(empty($url)) {
                continue;
            }
            $url = str_replace('/level/1/film/', '/film/', $url);

            $data[] = new KinopoiskParser('https://www.kinopoisk.ru' . $url);
        }
        return $data;
    }
}