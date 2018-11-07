<?php
namespace app\components;

use Yii;
use app\models\Proxy;
use yii\base\Object;
use Zend\Http;

class KinopoiskPremiereIterator extends Object implements \Iterator
{
    protected $position = 0;

    protected $data;

    protected $baseUrl;

    public function __construct($url, array $config = [])
    {
        $this->baseUrl = $url;
        $this->loadData();
        parent::__construct($config);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function next()
    {
        ++$this->position;
    }

    protected function loadData()
    {
        $client = new Http\Client($this->baseUrl);
        Proxy::rand()->apply($client);
        $response = $client->send();
        preg_match("#xsrftoken = '(.+?)';#", $response->getBody(), $matches);
        $token = $matches[1];
        $data = [];
        \phpQuery::newDocumentHTML($response->getBody());
        foreach(pq('.premier_item') as $item) {
            $id = pq($item)->attr('id');
            $data[] = new KinopoiskParser("https://www.kinopoisk.ru/film/{$id}/");
        }
        for($page = 1; $page < 10; $page++) {
            $client->resetParameters();
            $client->setUri('https://www.kinopoisk.ru/premiere/us/');
            $client->setParameterPost([
                'token' => $token,
                'page' => $page,
                'ajax' => true
            ]);
            $client->setMethod('POST');
            $response = $client->send();

            \phpQuery::newDocumentHTML($response->getBody());
            if(pq('.premier_item')->length == 0) {
                break;
            }
            foreach(pq('.premier_item') as $item) {
                $id = pq($item)->attr('id');
                $data[] = new KinopoiskParser("https://www.kinopoisk.ru/film/{$id}/");
            }
        }
        $this->data = $data;
    }

    /**
     * Returns the index of the current dataset.
     * This method is required by the interface [[\Iterator]].
     * @return integer the index of the current row.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Returns the current dataset.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current dataset.
     */
    public function current()
    {

        return $this->data[$this->position];
    }

    /**
     * Returns whether there is a valid dataset at the current position.
     * This method is required by the interface [[\Iterator]].
     * @return boolean whether there is a valid dataset at the current position.
     */
    public function valid()
    {
        return isset($this->data[$this->position]);
    }
}