<?php
namespace app\assets;

use yii\web\AssetBundle;

class DepDropAsset extends AssetBundle
{
    public $sourcePath = '@bower/dependent-dropdown';

    public $js = [
        'js/dependent-dropdown.min.js',
    ];

    public $css = [
        'css/dependent-dropdown.min.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
