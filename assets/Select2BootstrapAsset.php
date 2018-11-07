<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class Select2BootstrapAsset extends AssetBundle
{
    public $sourcePath = '@bower/select2-bootstrap-theme/dist';

    public $css = [
        'select2-bootstrap.min.css'
    ];

    public $depends = [
        'app\assets\Select2Asset',
    ];
}