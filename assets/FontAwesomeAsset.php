<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class FontAwesomeAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/source/font-awesome';

    public $css = [
        'css/font-awesome.min.css'
    ];
}