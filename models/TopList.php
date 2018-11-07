<?php
namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%genre}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $kp_url
 * @property string $category
 */
class TopList extends ActiveRecord
{
    public $without_it;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%top_list}}';
    }

    /**
     * @param array $data
     * @return TopList
     */
    public static function findOrCreate(array $data)
    {
        $model = self::findOne($data);
        if($model == null) {
            $model = new self;
            $model->setAttributes($data, false);
            $model->save(false);
        }
        return $model;
    }

    /**
     * @return array
     */
    public static function ownList()
    {
        return ArrayHelper::map(self::find()->all(), 'id', 'name', 'category');
    }
}