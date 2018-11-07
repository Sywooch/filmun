<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_person}}".
 *
 * @property integer $film_id
 * @property integer $user_id
 * @property integer $mark
 */
class FilmMark extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_mark}}';
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'film_id',
            'user_id',
            'mark',
        ];
    }

    /**
     * @param $film_id
     * @param $user_id
     * @param $mark
     */
    public static function create($film_id, $user_id, $mark)
    {
        if(empty($mark)) {
            $sql = 'DELETE FROM {{%film_mark}} WHERE film_id = :film_id AND user_id = :user_id';
            Yii::$app->db->createCommand($sql, [
                'film_id' => $film_id,
                'user_id' => $user_id,
            ])->execute();
        } else {
            $sql = 'INSERT IGNORE INTO {{%film_mark}} (film_id, user_id, mark, created_at) VALUES(:film_id, :user_id, :mark, :created_at) ON DUPLICATE KEY UPDATE mark=:mark';
            Yii::$app->db->createCommand($sql, [
                'film_id' => $film_id,
                'user_id' => $user_id,
                'mark' => $mark,
                'created_at' => time(),
            ])->execute();
        }
    }
}