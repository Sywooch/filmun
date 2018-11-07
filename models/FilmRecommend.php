<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_recommend}}".
 *
 * @property integer $film_id
 * @property integer $user_id
 */
class FilmRecommend extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_recommend}}';
    }

    /**
     * @param $film_id
     * @param $user_id
     */
    public static function create($film_id, $user_id)
    {
        Yii::$app->db->createCommand('INSERT IGNORE INTO {{%film_recommend}} (film_id, user_id) VALUES(:film_id, :user_id)', [
            'film_id' => $film_id,
            'user_id' => $user_id,
        ])->execute();
    }
}