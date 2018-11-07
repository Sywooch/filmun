<?php
namespace app\widgets;

use app\assets\DepDropAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use yii\helpers\Json;

class DepDrop extends InputWidget
{
    public $depends;

    public $url;

    public $items = [];

    public $prompt = false;

    public $clientOptions = [];

    public $options = [];

    public function init()
    {
        if(empty($this->options['id']))
            $this->options['id'] = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->name;
        if(empty($this->options['class']))
            $this->options['class'] = 'form-control';
        if($this->prompt !== false)
            $this->options['prompt'] = $this->prompt;
        else if(isset($this->options['prompt']))
            $this->prompt = $this->options['prompt'];

        parent::init();
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeDropDownList($this->model, $this->attribute, $this->items, $this->options);
        } else {
            echo Html::dropDownList($this->name, $this->value, $this->items, $this->options);
        }
        $this->registerClientScript();
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $options = $this->getClientOptions();
        $options = Json::encode($options);
        $id = $this->options['id'];
        $js = "jQuery(\"#{$id}\").depdrop({$options});";
        $view = $this->getView();
        DepDropAsset::register($view);
        $view->registerJs($js);
    }

    /**
     * @return array the options for the text field
     */
    protected function getClientOptions()
    {
        $options = $this->clientOptions;
        $options['url'] = Url::to($this->url);
        $options['placeholder'] = $this->prompt;
        $options['depends'] = is_string($this->depends) ? [$this->depends] : $this->depends;
        if(empty($options['loadingText']))
            $options['loadingText'] = 'Загрузка...';

        return $options;
    }
}
