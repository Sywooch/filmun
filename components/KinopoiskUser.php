<?php
namespace app\components;


use app\models\User;
use yii\base\Exception;
use yii\base\Object;
use Zend\Http;

class KinopoiskUser extends Object
{
    protected $user;

    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;
        parent::__construct($config);
    }

    public function putMark($url, $kp_internal_id, $mark)
    {
        $user = $this->user;

        $client = new Http\Client();
        $logged = KinopoiskParser::login($client, $user->kp_login, $user->kp_password);
        if(!$logged) {
            throw new Exception('Не удалось авторизироваться');
        }
        $client->setUri($url);
        $client->setOptions(['maxredirects' => 5]);
        $response = $client->send();

        if($mark) {
            $data = [
                'id_film' => $kp_internal_id,
                'vote' => $mark,
                'export' => '',
                'comment' => '',
                'rnd' => microtime(),
            ];
            preg_match("#xsrftoken = '(.+?)';#", $response->getBody(), $matches);
            $data['token'] = $matches[1];
            preg_match("#user_code:'(.+?)',#", $response->getBody(), $matches);
            $data['c'] = $matches[1];
        } else {
            $data = [
                'id_film' => $kp_internal_id,
                'act' => 'kill_vote',
            ];
            preg_match("#xsrftoken = '(.+?)';#", $response->getBody(), $matches);
            $data['token'] = $matches[1];
        }

        foreach($response->getCookie() as $cookie) {
            $client->addCookie($cookie->getName(), $cookie->getValue());
        }
        $client->resetParameters();
        $client->setUri('https://www.kinopoisk.ru/handler_vote.php');
        $client->setParameterGet($data);
        $response = $client->send();

        return $response->getStatusCode() == 200;
    }

    public function wanted($url, $kp_internal_id, $mode)
    {
        $user = $this->user;
        $json = [];

        $client = new Http\Client();
        $logged = KinopoiskParser::login($client, $user->kp_login, $user->kp_password);
        if(!$logged) {
            $json['notice'] = [
                'message' => 'Не удалось авторизироваться',
                'type' => 'danger',
            ];
            return $json;
        }
        $client->setUri($url);
        $client->setOptions(['maxredirects' => 5]);
        $response = $client->send();

        preg_match("#xsrftoken = '(.+?)';#", $response->getBody(), $matches);
        $token = $matches[1];

        foreach($response->getCookie() as $cookie) {
            $client->addCookie($cookie->getName(), $cookie->getValue());
        }

        $client->resetParameters();

        $client->setUri('https://www.kinopoisk.ru/handler_mustsee_ajax.php');

        $params = [
            'token' => $token,
            'id_film' => $kp_internal_id,
            'rnd' => microtime(),
        ];
        if($mode == 'add') {
            $params['to_folder'] = 3575;
            $params['mode'] = 'add_film';
        } else {
            $params['from_folder'] = 3575;
            $params['mode'] = 'del_film';
        }
        $client->setParameterGet($params);
        $response = $client->send();

        return $response->getStatusCode() == 200;
    }
}