<?php
namespace app\models;

use Yii;
use yii\helpers\Html;

class Director extends Person
{
    protected static $favourite_ids;

    public function getNameTag()
    {
        if(self::$favourite_ids === null) {
            self::$favourite_ids = PersonFavourite::find()->andWhere(['user_id' => user()->id])->select('person_id')->column();
        }
        $options = [];
        if(in_array($this->id, self::$favourite_ids)) {
            Html::addCssStyle($options, 'color: #19b735;font-weight: 600;');
        } else {
            Html::addCssStyle($options, 'color:#8e8e8e;');
        }
        return Html::tag('span', $this->name, $options);
    }
}