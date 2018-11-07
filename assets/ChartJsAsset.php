<?php
namespace app\assets;

use yii\web\AssetBundle;

class ChartJsAsset extends AssetBundle
{
    //public $sourcePath = '@bower/chartjs';

    public $js = [
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.4/Chart.bundle.min.js',
    ];

    public $css = [];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}