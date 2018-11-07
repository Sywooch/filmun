<?php
namespace app\assets;

use Yii;
use yii\web\View;
use yii\web\AssetBundle;

class MomentAsset extends AssetBundle
{
    public $sourcePath = '@bower/moment';

    public $js = [
        'min/moment-with-locales.min.js',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];

    public $language;

    public function init()
    {
        $language = $this->language ? $this->language : \Yii::$app->language;
        if($language != 'en-US') {
            $language = substr($language, 0, 2);
            Yii::$app->view->registerJs("moment.locale('$language');", View::POS_READY);
        }
        parent::init();
    }
}
