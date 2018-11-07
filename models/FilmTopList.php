<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_top_list}}".
 *
 * @property integer $film_id
 * @property integer $top_list_id
 */
class FilmTopList extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_top_list}}';
    }

    /**
     * @param $film_id
     * @param $top_list_id
     */
    public static function create($film_id, $top_list_id)
    {
        Yii::$app->db->createCommand('INSERT IGNORE INTO {{%film_top_list}} (film_id, top_list_id) VALUES(:film_id, :top_list_id)', [
            'film_id' => $film_id,
            'top_list_id' => $top_list_id,
        ])->execute();
    }
}