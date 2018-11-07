<?php
namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

class YandexMapAsset extends AssetBundle
{
    public $jsOptions = [
        'position' => View::POS_HEAD,
        'type' => 'text/javascript',
    ];

    public $js = [
        'http://api-maps.yandex.ru/2.1/?lang=ru_RU'
    ];
}
