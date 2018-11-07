<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class IonRangeSliderAsset extends AssetBundle
{
    public $sourcePath = '@bower/ion.rangeslider';

    public $js = [
        'js/ion.rangeSlider.min.js',
    ];

    public $css = [
        'css/ion.rangeSlider.css',
        //'css/ion.rangeSlider.skinFlat.css',
        'css/ion.rangeSlider.skinNice',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'app\assets\MomentAsset',
    ];
}
