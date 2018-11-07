<?php
namespace app\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;
use yii\helpers\Url;
use app\assets\AutoCompleteAsset;

class AutoComplete extends InputWidget
{
    public $source;

    public $clientOptions = [];

    public $minLength = 2;

    public $change;

    public $placeholder;

    public $options = [];

    public $removeIfInvalid = false;

    public $keyElement;

    public function init()
    {
        parent::init();
        if($this->placeholder) {
            $this->options['placeholder'] = $this->placeholder;
        }
        $this->options = ArrayHelper::merge([
            'class' => 'form-control'
        ], $this->options);
    }

    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textInput($this->name, $this->value, $this->options);
        }
        $this->registerClientScript();
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        AutoCompleteAsset::register($view);

        $options = $this->getClientOptions();
        $options = Json::encode($options);
        $id = $this->options['id'];
        $js = "jQuery(\"#{$id}\").autocomplete({$options});";
        $view->registerJs($js);
    }

    /**
     * @return array the options for the text field
     */
    protected function getClientOptions()
    {
        $options = $this->clientOptions;

        if(is_array($this->source))
            $options['source'] = Url::to($this->source);
        else if($this->source)
            $options['source'] = new JsExpression($this->source);

        if($this->minLength)
            $options['minLength'] = $this->minLength;

        if($this->removeIfInvalid)
            $options['removeIfInvalid'] = $this->removeIfInvalid;

        if($this->keyElement)
            $options['keyElement'] = $this->keyElement;

        if($this->change) {
            $options['change'] = new JsExpression($this->change);
        }

        return $options;
    }
}