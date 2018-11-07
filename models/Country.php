<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * This is the model class for table "{{%country}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $rating
 * @property integer $kp_internal_id
 */
class Country extends ActiveRecord
{
    public $without_it;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%country}}';
    }

    public function fields()
    {
        return [
            'id',
            'name',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return parent::find()->orderBy(['rating' => SORT_DESC]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'kp_internal_id'], 'required'],
            [['kp_internal_id'], 'integer'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'kp_internal_id' => 'Kp Internal ID',
        ];
    }

    /**
     * @param $data
     * @return Genre|static
     */
    public static function findOrCreate($data)
    {
        $kp_internal_id = ArrayHelper::remove($data, 'kp_internal_id');
        $model = self::findOne(['kp_internal_id' => $kp_internal_id]);
        if($model == null) {
            $model = new self;
            $model->kp_internal_id = $kp_internal_id;
            $model->setAttributes($data, false);
            $model->save(false);
        }
        return $model;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return Html::a($this->name, ['country/view', 'id' => $this->id]);
    }
}