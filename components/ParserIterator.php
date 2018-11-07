<?php
namespace app\components;

use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use Zend\Http;

abstract class ParserIterator extends Object implements \Iterator
{
    /**
     * @var array the data retrieved in the current batch
     */
    protected $_batch;
    /**
     * @var mixed the value for the current iteration
     */
    protected $_value;
    /**
     * @var string|integer the key for the current iteration
     */
    protected $_key;

    protected $_page = 1;

    protected $baseUrl;

    protected $_totalPages;

    public function __construct($url, array $config = [])
    {
        $this->baseUrl = $url;
        parent::__construct($config);
    }

    /**
     * @return float|int
     */
    abstract public function getTotalPages();

    /**
     * Resets the batch query.
     * This method will clean up the existing batch query so that a new batch query can be performed.
     */
    public function reset()
    {
        $this->_batch = null;
        $this->_value = null;
        $this->_key = null;
        $this->_page = 1;
        $this->_totalPages = null;
    }

    /**
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     */
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    public function next()
    {
        if ($this->_batch === null || next($this->_batch) === false) {
            $this->_batch = $this->fetchData();

            reset($this->_batch);
        }
        $this->_value = current($this->_batch);
        $this->_key++;
    }

    /**
     * Fetches the next batch of data.
     * @return array the data fetched
     */
    abstract protected function fetchData();

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
        return $this->_value;
    }

    /**
     * Returns whether there is a valid dataset at the current position.
     * This method is required by the interface [[\Iterator]].
     * @return boolean whether there is a valid dataset at the current position.
     */
    public function valid()
    {
        return !empty($this->_batch);
    }
}
