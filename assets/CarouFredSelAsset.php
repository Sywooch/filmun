<?php
namespace app\assets;

use yii\web\AssetBundle;

class CarouFredSelAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/source/jquery-carouFredSel';

    public $js = [
        'jquery.carouFredSel-6.2.1.js',
    ];

    public $css = [];

    public $depends = [
        'yii\web\JqueryAsset',
        'app\assets\MousewheelAsset',
    ];

    public $helpers = ['touch-swipe']; // throttle-debounce touch-swipe transit

    public function registerAssetFiles($view)
    {
        if(in_array('throttle-debounce', $this->helpers)) {
            $this->js[] = 'helper-plugins/jquery.ba-throttle-debounce.min.js';
        }
        if(in_array('touch-swipe', $this->helpers)) {
            $this->js[] = 'helper-plugins/jquery.touchSwipe.min.js';
        }
        if(in_array('transit', $this->helpers)) {
            $this->js[] = 'helpers/jquery.transit.min.js';
        }
        parent::registerAssetFiles($view);
    }
}