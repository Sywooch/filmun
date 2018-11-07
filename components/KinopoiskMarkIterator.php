<?php
namespace app\components;

use Yii;
use yii\helpers\ArrayHelper;
use Zend\Http;

class KinopoiskMarkIterator extends ParserIterator
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
    protected function getBaseUser()
    {
        if($this->_base_url == null) {
            $html = $this->getHtml('https://www.kinopoisk.ru/');

            preg_match('#userId: ([0-9]+)#', $html, $matches);

            $user_id = ArrayHelper::getValue($matches, 1);
            $this->_base_url = "https://www.kinopoisk.ru/user/{$user_id}/votes/list/ord/date/perpage/200/";
        }
        return $this->_base_url;
    }

    /**
     * @return float|int
     */
    public function getTotalPages()
    {
        if($this->_totalPages === null) {
            $html = $this->getHtml($this->getBaseUser());
            \phpQuery::newDocumentHTML($html);
            $url = pq('#list .navigator .list li.arr:last-child a')->attr('href');

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
        $pageUrl = trim($this->getBaseUser(), '/') . '/page/' . $page . '/';

        $content = $this->getHtml($pageUrl);
        $data = [];

        \phpQuery::newDocumentHtml($content);
        foreach(pq('.profileFilmsList .item') as $div) {
            $url = trim(pq('.nameRus > a', $div)->attr('href'));
            if(empty($url)) {
                continue;
            }

            $jsCode = pq('script', $div)->html();
            preg_match("#rating: '([0-9]+)'#", $jsCode, $matches);
            $params = [
                'myMark' => ArrayHelper::getValue($matches, 1),
            ];

            $url = str_replace('/level/1/film/', '/film/', $url);
            $data[] = new KinopoiskParser('https://www.kinopoisk.ru' . $url, $params);
        }
        return $data;
    }
}