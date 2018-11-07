<?php
namespace app\components;

use Yii;
use yii\helpers\ArrayHelper;
use Zend\Http;

class KinopoiskMyListIterator extends ParserIterator
{
    public $dir_id = 3575;

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
            $this->_base_url = "https://www.kinopoisk.ru/mykp/movies/list/type/{$this->dir_id}/perpage/200/";
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
            $data_id = (int) pq($div)->attr('data-id');
            if(empty($data_id)) {
                continue;
            }
            $url = "https://www.kinopoisk.ru/film/{$data_id}/";
            $data[] = new KinopoiskParser($url);
        }
        return $data;
    }
}