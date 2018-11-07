<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class BootstrapSelectAsset extends AssetBundle
{
    // изменял файл. добавил loadData
    public $sourcePath = '@app/assets/source/bootstrap-select';

    public $js = [
        'js/bootstrap-select.js',
        'js/i18n/defaults-ru_RU.js',
    ];

    public $css = [
        'css/bootstrap-select.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'app\assets\UnderscoreAsset',
    ];
}
