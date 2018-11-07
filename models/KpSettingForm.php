<?php
namespace app\models;

use Yii;
use Zend\Http;
use yii\base\Model;
use app\components\KinopoiskParser;

class KpSettingForm extends Model {

    private $_user;

    public $kp_login;

    public $kp_password;

    public function __construct(User $user, $config = [])
    {
        $this->_user = $user;
        $this->setAttributes($user->getAttributes());
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['kp_login', 'kp_password'], 'required'],
            [['kp_password'], 'checkPassword'],
        ];
    }

    public function checkPassword()
    {
        $client = new Http\Client;
        $valid = KinopoiskParser::login($client, $this->kp_login, $this->kp_password);
        if(!$valid) {
            $this->addError('kp_password', 'Не правильный логин или пароль');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'kp_login' => ('Логин КиноПоиск'),
            'kp_password' => ('Пароль КиноПоиск'),
        ];
    }

    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        $user->kp_login = $this->kp_login;
        $user->kp_password = $this->kp_password;
        $user->save(false, ['kp_login', 'kp_password_hash']);
        //$user->save(false);

        return true;
    }
} 