<?php
namespace app\assets;

use yii\web\AssetBundle;

class FancyboxAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/source/fancybox/source';

    public $js = [
        'jquery.fancybox.js',
    ];

    public $css = [
        'jquery.fancybox.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'app\assets\MousewheelAsset',
    ];

    public $helpers = []; // buttons thumbs media

    public function registerAssetFiles($view)
    {
        if(in_array('buttons', $this->helpers)) {
            $this->css[] = 'helpers/jquery.fancybox-buttons.css';
            $this->js[] = 'helpers/jquery.fancybox-buttons.js';
        }
        if(in_array('thumbs', $this->helpers)) {
            $this->css[] = 'helpers/jquery.fancybox-thumbs.css';
            $this->js[] = 'helpers/jquery.fancybox-thumbs.js';
        }
        if(in_array('media', $this->helpers)) {
            $this->js[] = 'helpers/jquery.fancybox-media.js';
        }
        parent::registerAssetFiles($view);
    }
}