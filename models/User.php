<?php
namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $name
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $access_token
 * @property string $email
 * @property string $telegram_id
 * @property string $auth_key
 * @property integer $role
 * @property integer $status
 * @property integer $desired_film_size
 * @property integer $notify_torrent_quality
 * @property integer $notify_torrent_transfer
 * @property string $kp_login
 * @property string $kp_password
 * @property string $kp_password_hash
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $last_check_at
 * @property integer $new_check_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DISABLE = '0';
    const STATUS_ACTIVE = '1';

    const ROLE_ADMIN = 'admin';
    const ROLE_GODMODE = 'godmode';
    const ROLE_EMPLOYEE = 'employee';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function fields()
    {
        return [
            'username',
            'role',
            'email',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'role', 'email'], 'required'],
            [['email', 'username'], 'filter', 'filter' => 'trim'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DISABLE]],

            ['role', 'default', 'value' => self::ROLE_ADMIN],
            ['role', 'in', 'range' => array_keys($this->getRoleList())],

            [['username', 'email'], 'unique'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            [['kp_login', 'kp_password'], 'string'],
            ['email', 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @param $value
     */
    public function setKp_password($value)
    {
        $this->kp_password_hash = base64_encode(Yii::$app->security->encryptByPassword($value, Yii::$app->params['security']));
    }

    /**
     * @return bool|string
     */
    public function getKp_password()
    {
        return Yii::$app->security->decryptByPassword(base64_decode($this->kp_password_hash), Yii::$app->params['security']);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'username' => Yii::t('app', 'Логин'),
            'password' => Yii::t('app', 'Пароль'),
            'role' => Yii::t('app', 'Роль'),
            'status' => Yii::t('app', 'Активный'),
            'created_at' => Yii::t('app', 'Добавлен'),
            'updated_at' => Yii::t('app', 'Изменен'),
            'last_visit_at' => Yii::t('app', 'Посл. визит'),
        ];
    }

    /**
     * @return array
     */
    public function getRoleList()
    {
        return [
            self::ROLE_ADMIN => Yii::t('app', 'Админ'),
            self::ROLE_GODMODE => Yii::t('app', 'Супер админ'),
            self::ROLE_EMPLOYEE => Yii::t('app', 'Сотрудник'),
        ];
    }

    /**
     * @return string
     */
    public function getRoleLabel()
    {
        return ArrayHelper::getValue($this->getRoleList(), $this->role);
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }
}
