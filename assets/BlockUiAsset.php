<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class BlockUiAsset extends AssetBundle
{
    public $sourcePath = '@bower/blockui';

    public $js = [
        'jquery.blockUI.js',
    ];

    public $css = [];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}