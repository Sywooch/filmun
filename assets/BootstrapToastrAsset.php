<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class BootstrapToastrAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/source/bootstrap-toastr';

    public $js = [
        'toastr.min.js'
    ];

    public $css = [
        'toastr.min.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}