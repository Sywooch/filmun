<?php
namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%person_favourite}}".
 *
 * @property integer $person_id
 * @property integer $user_id
 * @property string $roles
 *
 * @property Person $person
 */
class PersonFavourite extends ActiveRecord
{
    public function fields()
    {
        return [
            'id' => 'person_id',
            'name' => function(PersonFavourite $model){
                return $model->person->name;
            },
            'imageUrl' => function(PersonFavourite $model){
                return $model->person->getImageUrl();
            },
            'roles' => function(PersonFavourite $model){
                return $model->roles ? explode(',', $model->roles) : [
                    FilmPerson::ROLE_DIRECTOR,
                    FilmPerson::ROLE_ACTOR,
                    FilmPerson::ROLE_OPERATOR,
                    FilmPerson::ROLE_PRODUCER,
                    FilmPerson::ROLE_SCREENWRITER
                ];
            },
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person_favourite}}';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPerson()
    {
        return $this->hasOne(Person::className(), ['id' => 'person_id']);
    }

    /**
     * @param $person_id
     * @param $user_id
     * @param $role
     */
    public static function create($person_id, $user_id, $role)
    {
        $model = self::findOne(['person_id' => $person_id,  'user_id' => $user_id]);
        if($model === null) {
            $model = new PersonFavourite();
            $model->person_id = $person_id;
            $model->user_id = $user_id;
            $model->roles = $role;
        } else {
            $roles = $model->roles ? explode(',', $model->roles) : [];
            array_push($roles, $role);
            $roles = array_unique($roles);
            $model->roles = implode(',', $roles);
        }
        $model->save(false);
    }

    /**
     * @param $person_id
     * @param $user_id
     * @param string $role
     */
    public static function remove($person_id, $user_id, $role)
    {
        $model = self::findOne(['person_id' => $person_id,  'user_id' => $user_id]);
        if($model === null) {
            return;
        }

        $roles = $model->roles ? explode(',', $model->roles) : [];
        $key = array_search($role, $roles);
        if($key !== false) {
            unset($roles[$key]);
        }
        if($roles) {
            $model->roles = implode(',', $roles);
            $model->save(false);
        } else {
            $model->delete();
        }
    }
}