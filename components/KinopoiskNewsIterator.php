<?php
namespace app\components;

use Yii;
use app\models\Proxy;
use yii\base\Object;
use Zend\Http\Client;

class KinopoiskNewsIterator extends Object implements \Iterator
{
    protected $_items;

    protected $_key;

    public function __construct($url, array $config = [])
    {
        $this->_items = $this->loadXml($url);
        parent::__construct($config);
    }

    protected function loadXml($url)
    {
        $client = new Client($url);
        Proxy::rand()->apply($client);
        $response = $client->send();
        $xmlString = $response->getBody();
        $xmlString = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $xmlString);
        $xmlString = str_replace('&utm', '&amp;utm', $xmlString);
        $xmlString = str_replace('&mdash;', '-', $xmlString);
        $xmlString = str_replace('&id', '&amp;id', $xmlString);
        $parsers = [];
        $xml = new \SimpleXMLElement($xmlString);
        foreach($xml->channel->item as $item) {
            $parsers[] = new KinopoiskNewsParser((string) $item->link, [
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'public_at' => strtotime($item->pubDate),
            ]);
        }
        return $parsers;
    }

    /**
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     */
    public function rewind()
    {
        $this->_key = 0;
        reset($this->_items);
    }

    public function next()
    {
        next($this->_items);
        $this->_key++;
    }

    /**
     * Returns the index of the current dataset.
     * This method is required by the interface [[\Iterator]].
     * @return integer the index of the current row.
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * Returns the current dataset.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current dataset.
     */
    public function current()
    {
        return $this->_items[$this->_key];
    }

    /**
     * Returns whether there is a valid dataset at the current position.
     * This method is required by the interface [[\Iterator]].
     * @return boolean whether there is a valid dataset at the current position.
     */
    public function valid()
    {
        return isset($this->_items[$this->_key]);
    }
}