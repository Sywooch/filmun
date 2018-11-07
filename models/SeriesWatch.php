<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%series_watch}}".
 *
 * @property integer $film_id
 * @property integer $user_id
 */
class SeriesWatch extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%series_watch}}';
    }
}