<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_wanted}}".
 *
 * @property integer $film_id
 * @property integer $user_id
 */
class FilmWanted extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_wanted}}';
    }

    /**
     * @param $film_id
     * @param $user_id
     */
    public static function create($film_id, $user_id)
    {
        Yii::$app->db->createCommand('INSERT IGNORE INTO {{%film_wanted}} (film_id, user_id, created_at) VALUES(:film_id, :user_id, :created_at)', [
            'film_id' => $film_id,
            'user_id' => $user_id,
            'created_at' => time(),
        ])->execute();
    }
}