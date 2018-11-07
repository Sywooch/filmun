<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%film_person}}".
 *
 * @property integer $film_id
 * @property integer $person_id
 * @property string $role
 */
class FilmPerson extends ActiveRecord
{
    const ROLE_DIRECTOR = 'director';
    const ROLE_SCREENWRITER = 'screenwriter';
    const ROLE_OPERATOR = 'operator';
    const ROLE_PRODUCER = 'producer';
    const ROLE_ACTOR = 'actor';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film_person}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['film_id', 'person_id', 'role'], 'required'],
            [['film_id', 'person_id'], 'integer'],
            [['role'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'film_id' => 'Film ID',
            'person_id' => 'Person ID',
            'role' => 'Role',
        ];
    }

    /**
     * @param $film_id
     * @param $person_id
     * @param $role
     * @param int $position
     */
    public static function create($film_id, $person_id, $role, $position = 1)
    {
        $sql = 'INSERT IGNORE INTO {{%film_person}} (film_id, person_id, role, position) VALUES(:film_id, :person_id, :role, :position) ON DUPLICATE KEY UPDATE position=:position';
        Yii::$app->db->createCommand($sql, [
            'film_id' => $film_id,
            'person_id' => $person_id,
            'role' => $role,
            'position' => $position
        ])->execute();
    }
}
