<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;

class JqueryBarRatingAsset extends AssetBundle
{
    public $sourcePath = '@bower/jquery-bar-rating/dist';

    public $js = [
        'jquery.barrating.min.js',
    ];

    public $css = [
        'themes/fontawesome-stars-o.css',
        'themes/css-stars.css',
        'themes/bootstrap-stars.css',
        'themes/bars-movie.css',
        'themes/bars-horizontal.css',
        'themes/bars-1to10.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
