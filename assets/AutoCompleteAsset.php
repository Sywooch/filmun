<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class AutoCompleteAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/source/autocomplete';

    public $js = [
        'jquery.autocomplete.js',
    ];

    public $css = [
        'jquery.autocomplete.css'
    ];

    public $depends = [
        'yii\jui\JuiAsset',
    ];
} 