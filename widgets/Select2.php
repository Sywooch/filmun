<?php
namespace app\widgets;

use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\InputWidget;
use yii\helpers\ArrayHelper;
use app\assets\Select2BootstrapAsset;

/**
 * Class Select2
 */
class Select2 extends InputWidget
{
    public $data;

    public $source;

    public $placeholder;

    public $clientOptions = [];

    public $maximumSelectionLength = 0;

    public $minimumResultsForSearch = 0;

    public $minimumInputLength = 0;

    public $maximumInputLength = 0;

    public $ajaxMinimumInputLength;

    public $multiple = false;

    public $prompt;

    public $theme = 'bootstrap';

    public $tags = false;

    public $tokenSeparators = [','];

    public $escape = false;

    public $allowClear = true;

    public $closeOnSelect = false;

    public $selectOnClose = false;

    public $pageSize = 10;

    public $disabled = false;

    public $readonly = false;

    public $optionLabel;

    public function init()
    {
        if($this->getId(false))
            $this->options['id'] = $this->getId(false);
        if(empty($this->options['id']))
            $this->options['id'] = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->id;

        Html::addCssClass($this->options, 'form-control');
        if($this->multiple) {
            $this->options['multiple'] = 'multiple';
        }
        if($this->disabled) {
            $this->options['disabled'] = true;
        }
        if($this->readonly) {
            $this->options['disabled'] = true;
        }
        if($this->prompt !== null) {
            $this->options['prompt'] = $this->prompt;
        }
        Html::addCssStyle($this->options, 'width: 100%');

        if($this->allowClear && empty($this->placeholder)) {
            $this->placeholder = '';
        }
        if($this->placeholder !== false) {
            $this->options['prompt'] = $this->placeholder;
        }
        if ($this->hasModel() && $this->value === null) {
            $this->value = Html::getAttributeValue($this->model, $this->attribute);
        }
        if($this->multiple) {
            $this->allowClear = false;
        }
        parent::init();
    }

    protected function getItems()
    {
        if(is_array($this->data)) {
            return $this->data;
        }
        if(empty($this->value)) {
            return [];
        }
        if(is_string($this->data) && class_exists($this->data)) {
            $model = $this->data;
            $label = $this->optionLabel ? $this->optionLabel : $model::nameAttribute();
            return ArrayHelper::map($model::findAll($this->value), $model::primaryKey(), $label);
        }
        return [];
    }

    public function run()
    {
        if($this->readonly) {
            if ($this->hasModel()) {
                echo Html::activeHiddenInput($this->model, $this->attribute, ['id' => null]);
            } else {
                echo Html::hiddenInput($this->name, $this->value);
            }
        }
        if ($this->hasModel()) {
            echo Html::activeDropDownList($this->model, $this->attribute, $this->getItems(), $this->options);
        } else {
            echo Html::dropDownList($this->name, $this->value, $this->getItems(), $this->options);
        }
        $this->registerClientScript();
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        Select2BootstrapAsset::register($view);

        $options = $this->getClientOptions();
        $options = Json::encode($options);
        $id = $this->options['id'];
        $js = "jQuery(\"#{$id}\").select2({$options});";
        $view->registerJs($js);
    }

    /**
     * @return array the options for the text field
     */
    protected function getClientOptions()
    {
        $options = ArrayHelper::merge([
            'theme' => $this->theme,
            'maximumSelectionLength' => $this->maximumSelectionLength,
            'placeholder' => $this->placeholder,
            'tags' => $this->tags,
            'allowClear' => $this->allowClear,
            'closeOnSelect' => $this->closeOnSelect,
            'minimumInputLength' => $this->minimumInputLength,
            'maximumInputLength' => $this->maximumInputLength,
            'minimumResultsForSearch' => $this->minimumResultsForSearch,
            'selectOnClose' => $this->selectOnClose,
        ], $this->clientOptions);

        if($options['tags'] && empty($options['tokenSeparators'])) {
            $options['tokenSeparators'] = $this->tokenSeparators;
        }

        $source = null;
        if(is_array($this->source))
            $source = Url::to($this->source);
        else if($this->source)
            $source = new JsExpression($this->source);
        if($source) {
            $options['ajax'] = [
                'url' => $source,
                'delay' => 250,
                'cache' => true,
            ];
            if($this->pageSize !== false) {
                $options['ajax']['processResults'] = new JsExpression("function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: (params.page * {$this->pageSize}) < data.total_count
                        }
                    };
                }");
            } else {
                $options['ajax']['processResults'] = new JsExpression("function (data, params) { return {results: data.items }; }");
            }
            $options['minimumInputLength'] = $this->ajaxMinimumInputLength;
        }

        if(!$this->escape) {
            $options['escapeMarkup'] = new JsExpression('function (markup) { return markup; }');
        }

        $options['templateResult'] = new JsExpression("function(item) { if (item.loading) return item.text; return item.content || item.text; }");

        return $options;
    }
}