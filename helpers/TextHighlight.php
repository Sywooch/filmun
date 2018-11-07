<?php
namespace app\helpers;
use yii\base\Object;

use Yii;
use yii\helpers\Html;

class TextHighlight extends Object
{
    public static function search($subject, $sArray)
    {
        is_array($sArray) or $sArray = [$sArray];
        foreach($sArray as $search) {
            $subject = str_ireplace($search, Html::tag('span', $search, ['style' => 'background: #BEEAFF;']), $subject);
        }
        return $subject;
    }
}