<?php
namespace app\models;

use Yii;
use yii\base\Model;

class ChangePasswordForm extends Model {

    private $_user;

    public $password;

    public $password_repeat;

    public function __construct(User $user, $config = [])
    {
        $this->_user = $user;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['password', 'password_repeat'], 'required'],
            [['password'], 'string', 'min' => 4],
            ['password_repeat', 'compare', 'compareAttribute' => 'password'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'password' => ('Пароль'),
            'password_repeat' => ('Пароль повторно'),
        ];
    }

    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        $user->setPassword($this->password);
        $user->save(true, ['password_hash']);

        /*Yii::$app->mailer->compose('changePassword', ['user' => $user, 'password' => $this->password])
            ->setFrom([Yii::$app->params['noReplyEmail'] => Yii::$app->name . ' robot'])
            ->setTo($user->email)
            ->setSubject(('Изменение пароля'))
            ->send();*/

        return true;
    }
} 