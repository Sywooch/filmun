<?php
namespace app\components;

use app\models\Proxy;
use Yii;
use Zend\Http;

class RutrackerIterator extends ParserIterator
{
    public $proxy = true;

    public function getHtml($url)
    {
        for ($i = 1; $i < 10; $i++) {
            try {
                $client = new Http\Client($url);
                $client->setAdapter('Zend\Http\Client\Adapter\Curl');
                $client->setUri($url);
                $client->setHeaders(array_merge(RutrackerParser::headers(), [
                    'Referer' => 'https://www.google.com.ua/',
                ]));
                if($this->proxy) {
                    Proxy::rand()->apply($client);
                }
                $response = $client->send();
                break;
            } catch (\Exception $e) {}
        }
        return $response->getBody();
    }

    /**
     * @return float|int
     */
    public function getTotalPages()
    {
        if($this->_totalPages === null) {
            $htmlBody = $this->getHtml($this->baseUrl);
            \phpQuery::newDocumentHTML($htmlBody);

            $maxPages = 1;

            foreach(pq('.pg') as $a) {
                $text = pq($a)->text();
                $text = preg_replace('#[^0-9]+#', '', $text);

                $pages = (int) $text;
                $maxPages = max($pages, $maxPages);
            }
            $this->_totalPages = $maxPages;
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

        $pageUrl = $this->baseUrl . '&start=' . (($page-1)*50);

        $htmlBody = $this->getHtml($pageUrl);
        $data = [];
        \phpQuery::newDocumentHtml($htmlBody);
        foreach(pq('.vf-col-t-title .torTopic .tt-text') as $a) {
            $url = trim(pq($a)->attr('href'));
            if(empty($url)) {
                continue;
            }

            $parser = new RutrackerParser('https://rutracker.org/forum/' . $url);
            $parser->proxy = $this->proxy;
            $data[] = $parser;
        }
        return $data;
    }
}