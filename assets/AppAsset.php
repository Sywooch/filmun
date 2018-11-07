<?php
namespace app\assets;

use Yii;
use yii\web\AssetBundle;
use yii\web\View;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
        'js/site.js',
        'js/modal.js',
        'js/film-view.js',
        'js/film-sidebar.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'app\assets\UnderscoreAsset',
        'app\assets\FancyboxAsset',
        'app\assets\FontAwesomeAsset',
        'app\assets\JgrowlAsset',
        'app\assets\JqueryBarRatingAsset',
        'app\assets\BootstrapToastrAsset',
        'app\assets\IonRangeSliderAsset',
        'app\assets\Select2BootstrapAsset',
        'app\assets\BlockUiAsset',
        'yii\jui\JuiAsset',
    ];

    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);
        $homeUrl = rtrim(Yii::$app->homeUrl, '/');
        $view->registerJs("
            var url = function(route, params){
                var url = route.startsWith('/') ? route : '{$homeUrl}' + '/' + route;
                if(params)
                   url += '?' + $.param(params)
                return url;
            }
        ", View::POS_HEAD);
        $view->registerJs("
            $.ajaxPrefilter(function(options) {
                if(options.crossDomain == false && options.url.slice(0,5) != 'http:' && options.url.slice(0,1) != '/') {
                    options.url = url(options.url);
                }
            });
        "); // в бутстрапе был margin-left:-20px на класс radio. Потому нажатие на радиобаттон было смещено при использовании uniform
    }
}
