<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class Select2Asset extends AssetBundle
{
    public $sourcePath = '@bower/select2/dist';

    public $js = [
        'js/select2.full.js',
        'js/i18n/ru.js'
    ];

    public $css = [
        'css/select2.min.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
