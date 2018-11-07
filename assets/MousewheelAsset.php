<?php
namespace app\assets;

use yii\web\AssetBundle;

class MousewheelAsset extends AssetBundle
{
    public $sourcePath = '@bower/jquery-mousewheel';

    public $js = [
        'jquery.mousewheel.min.js',
    ];

    public $css = [];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}