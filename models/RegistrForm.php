<?php
namespace app\models;

use Yii;
use yii\base\Model;

class RegistrForm extends Model
{
    public $username;

    public $password;

    public $email;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password', 'email'], 'required'],
            [['password'], 'string', 'min' => 6, 'max' => 16],
            [['username'], 'string', 'min' => 3, 'max' => 16],
            ['email', 'email'],
            ['username', 'validateUsername'],
        ];
    }

    public function validateUsername()
    {
        if (!$this->hasErrors()) {
            $exists = User::find()->andWhere(['username' => $this->username])->exists();

            if ($exists) {
                $this->addError('username', 'Логин уже используется.');
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'username' => Yii::t('app', 'Логин'),
            'password' => Yii::t('app', 'Пароль'),
            'email' => 'Email',
        ];
    }

    public function perform()
    {
        if(!$this->validate()) {
            return false;
        }

        $model = new User();
        $model->username = $this->username;
        $model->name = $this->username;
        $model->email = $this->email;
        $model->created_at = time();
        $model->updated_at = time();
        $model->status = User::STATUS_ACTIVE;
        $model->setPassword($this->password);
        $model->desired_film_size = 4096;
        $model->notify_torrent_quality = Torrent::QUALITY_DVD_RIP;
        $model->notify_torrent_transfer = 5;
        $model->save(false);
    }
}