<?php
namespace app\components;

use Yii;
use Zend\Http;
use yii\helpers\ArrayHelper;

class KinopoiskRecommendIterator extends ParserIterator
{
    protected $_client;

    protected $login;

    protected $password;

    protected $_base_url;

    /**
     * KinopoiskMarkIterator constructor.
     * @param string $login
     * @param string $password
     * @param array $config
     */
    public function __construct($login, $password, array $config = [])
    {
        $this->login = $login;
        $this->password = $password;
        parent::__construct(null, $config);
    }

    public function getHtml($url)
    {
        if($this->_client === null) {
            $this->_client = new Http\Client;
            KinopoiskParser::login($this->_client, $this->login, $this->password);
        }
        $this->_client->resetParameters();
        $this->_client->setUri($url);
        for($i = 1; $i < 10; $i++) {
            try {
                $response = $this->_client->send();
                break;
            } catch(\Exception $e) {}
        }
        return $response->getBody();
    }

    /**
     * @return string
     */
    protected function getBaseUrl()
    {
        if($this->_base_url == null) {
            $this->_base_url = "https://www.kinopoisk.ru/recommend/type%5Bfilm%5D/film/type%5Bserial%5D/serial/type%5Bmult%5D/mult/perpage/200/";
        }
        return $this->_base_url;
    }

    /**
     * @return float|int
     */
    public function getTotalPages()
    {
        if($this->_totalPages === null) {
            $html = $this->getHtml($this->getBaseUrl());
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
        $pageUrl = trim($this->getBaseUrl(), '/') . '/page/' . $page . '/';

        $content = $this->getHtml($pageUrl);
        $data = [];

        \phpQuery::newDocumentHtml($content);
        foreach(pq('#itemList .item') as $div) {
            $href = pq('.info .name a[href*=film]', $div)->attr('href');
            if(empty($href)) {
                continue;
            }
            $url = "https://www.kinopoisk.ru" . $href;
            $data[] = new KinopoiskParser($url);
        }
        return $data;
    }
}