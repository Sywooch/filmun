<?php
namespace app\widgets;

use app\assets\BootstrapSelectAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;

class BootstrapSelect extends InputWidget
{
    public $items = [];

    public $options = [];

    public $clientOptions = [];

    public $multiple = true;

    public $width;

    public $prompt;

    public $depends;

    public $url;

    public $noneSelectedText = '';

    public $alignRight;

    public function init()
    {
        if(empty($this->options['id']))
            $this->options['id'] = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->name;
        if(empty($this->options['class']))
            $this->options['class'] = 'form-control';
        if($this->multiple)
            $this->options['multiple'] = true;
        if(count($this->items) == 0)
            $this->options['disabled'] = true;
        if(!$this->multiple && $this->prompt !== null) {
            $this->options['prompt'] = $this->prompt;
        }
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
        $js = "jQuery(\"#{$id}\").selectpicker({$options});";
        $view = $this->getView();
        BootstrapSelectAsset::register($view);
        $view->registerJs($js);

        if($this->depends) {
            $url = Json::encode(Url::to($this->url));
            $view->registerJs("
                $('#{$this->depends}').change(function() {
                    $('#{$id}').selectpicker('loadData', $url, {'depdrop_parents[0]': $(that).val()});
                })
            ");
        }
    }

    /**
     * @return array the options for the text field
     */
    protected function getClientOptions()
    {
        $options = $this->clientOptions;
        if($this->width)
            $options['width'] = $this->width;

        $options['hideDisabled'] = true;
        if($this->noneSelectedText !== null)
            $options['noneSelectedText'] = $this->noneSelectedText;
        if($this->prompt !== null)
            $options['prompt'] = $this->prompt;
        if($this->alignRight)
            $options['dropdownAlignRight'] = $this->alignRight;
        return $options;
    }
}