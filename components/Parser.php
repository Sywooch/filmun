<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

abstract class Parser extends Component
{
    public $url;

    public $params = [];

    /**
     * RutrackerParser constructor.
     * @param string $url
     * @param array $params
     * @param array $config
     */
    public function __construct($url, $params = [], $config = [])
    {
        $this->url = $url;
        $this->params = $params;
        parent::__construct($config);
    }

    /**
     * @param $name
     * @param $value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getParam($name)
    {
        return ArrayHelper::getValue($this->params, $name);
    }

    abstract public function loadContent();

    abstract public function clearContent();
}