<?php
namespace app\models;

use Yii;
use yii\base\Model;

class UserSettingForm extends Model
{
    private $_user;

    public $notify_torrent_quality;

    public $notify_torrent_transfer;

    public $desired_film_size;

    public $email;

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
            [['notify_torrent_quality', 'desired_film_size', 'notify_torrent_transfer'], 'integer'],
            [['email'], 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'notify_torrent_quality' => ('Оповещать о появлении на торрентах'),
            'notify_torrent_transfer' => ('Качество озвучки'),
            'desired_film_size' => ('Предпочитаемый размер видео'),
            'email' => ('Email'),
        ];
    }

    public function getNotifyTorrentQualityList()
    {
        return [
            Torrent::QUALITY_TS => 'CAMRip и выше',
            Torrent::QUALITY_DVD_SCR => 'DVDScr и выше',
            Torrent::QUALITY_TV_RIP => 'TVRip и выше',
            Torrent::QUALITY_DVD_RIP => 'DVDRip и выше',
            Torrent::QUALITY_HD_RIP => 'HDRip и выше',
            Torrent::QUALITY_BLU_RAY => 'BluRay',
        ];
    }

    public function getDesiredFilmSize()
    {
        return [
            1024*2 => '2 ГБ',
            1024*3 => '3 ГБ',
            1024*4 => '4 ГБ',
            1024*6 => '6 ГБ',
            1024*8 => '8 ГБ',
            1024*12 => '12 ГБ',
            1024*24 => '24 ГБ',
            1024*32 => '32 ГБ',
        ];
    }

    public function getNotifyTorrentTransfer()
    {
        return [
            7 => 'Полный дубляж',
            6 => 'Профессиональный закадровый',
            5 => 'Любительский многоголосый',
            4 => 'Любительский двухголосый',
            3 => 'Любительский одноголосый',
            2 => 'Субтитры',
            1 => 'Отсутствует',
        ];
    }

    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        $user->desired_film_size = $this->desired_film_size;
        $user->notify_torrent_quality = $this->notify_torrent_quality;
        $user->notify_torrent_transfer = $this->notify_torrent_transfer;
        $user->email = $this->email;
        $user->save(false, ['desired_film_size', 'notify_torrent_quality', 'notify_torrent_transfer', 'email']);

        return true;
    }
}