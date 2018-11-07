<?php
namespace app\components;

use Yii;
use Zend\Http;
use app\models\Proxy;

class NnmClubIterator extends ParserIterator
{
    public $proxy = false;

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
            foreach(pq('.gensmall:first-child b a') as $a) {
                $pages = (int) preg_replace('#[^0-9]+#', '', pq($a)->text());
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
        foreach(pq('.topictitle') as $h2) {
            $url = trim(pq('a', $h2)->attr('href'));
            if(empty($url)) {
                continue;
            }

            $parser = new NnmClubParser('http://nnm-club.me/forum/' . $url);
            $parser->proxy = $this->proxy;

            $data[] = $parser;
        }
        return $data;
    }
}
