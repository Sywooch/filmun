<?php
namespace app\assets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\AssetBundle;

class JgrowlAsset extends AssetBundle
{
    public $sourcePath = '@bower/jgrowl';

    public $js = [
        'jquery.jgrowl.js'
    ];

    public $css = [
        'jquery.jgrowl.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset'
    ];

    public function registerAssetFiles($view)
    {
        $message = 'закрыть все';
        $message = Html::tag('div', '[ ' . $message . ' ]');
        $message = Json::encode($message);
        $view->registerJs("$.jGrowl.defaults.closerTemplate = $message;");
        parent::registerAssetFiles($view);
    }
}