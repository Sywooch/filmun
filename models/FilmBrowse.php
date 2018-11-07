<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_person}}".
 *
 * @property integer $film_id
 * @property integer $torrent_id
 * @property integer $user_id
 * @property integer $created_at
 *
 * @property Film $film
 * @property Torrent $torrent
 */
class FilmBrowse extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_browse}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'film_id' => 'Фильм',
            'torrent_id' => 'Торрент',
            'user_id' => 'Пользователь',
            'created_at' => 'Дата просмотра',
        ];
    }

    /**
     * @param $film_id
     * @param $torrent_id
     * @param $user_id
     */
    public static function create($film_id, $torrent_id, $user_id)
    {
        $sql = 'INSERT IGNORE INTO {{%film_browse}} (film_id, torrent_id, user_id, created_at) VALUES(:film_id, :torrent_id, :user_id, :created_at) ON DUPLICATE KEY UPDATE created_at=:created_at';
        Yii::$app->db->createCommand($sql, [
            'film_id' => $film_id,
            'torrent_id' => $torrent_id,
            'user_id' => $user_id,
            'created_at' => time(),
        ])->execute();
    }

    public function getFilm()
    {
        return $this->hasOne(Film::className(), ['id' => 'film_id']);
    }

    public function getTorrent()
    {
        return $this->hasOne(Torrent::className(), ['id' => 'torrent_id']);
    }
}