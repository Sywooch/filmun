<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_genre}}".
 *
 * @property integer $film_id
 * @property integer $genre_id
 */
class FilmGenre extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_genre}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['film_id', 'genre_id'], 'required'],
            [['film_id', 'genre_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'film_id' => 'Film ID',
            'genre_id' => 'Genre ID',
        ];
    }

    /**
     * @param $film_id
     * @param $genre_id
     */
    public static function create($film_id, $genre_id)
    {
        Yii::$app->db->createCommand('INSERT IGNORE INTO {{%film_genre}} (film_id, genre_id) VALUES(:film_id, :genre_id)', [
            'film_id' => $film_id,
            'genre_id' => $genre_id
        ])->execute();
    }
}
