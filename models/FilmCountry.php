<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_country}}".
 *
 * @property integer $film_id
 * @property integer $country_id
 */
class FilmCountry extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_country}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['film_id', 'country_id'], 'required'],
            [['film_id', 'country_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'film_id' => 'Film ID',
            'country_id' => 'Country ID',
        ];
    }

    /**
     * @param $film_id
     * @param $country_id
     */
    public static function create($film_id, $country_id)
    {
        Yii::$app->db->createCommand('INSERT IGNORE INTO {{%film_country}} (film_id, country_id) VALUES(:film_id, :country_id)', [
            'film_id' => $film_id,
            'country_id' => $country_id
        ])->execute();
    }
}
