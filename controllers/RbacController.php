<?php
namespace app\controllers;

use Yii;
use yii\rbac\Role;
use yii\web\Controller;
use app\components\PhpManager;

class RbacController extends Controller
{
    /**
     * @var PhpManager
     */
    protected $auth;

    public function init()
    {
        /* @var PhpManager $auth */
        $this->auth = Yii::$app->authManager;
        $this->auth->removeAll();

        // роль гостя
        $guest = $this->auth->createRole('guest');
        $this->auth->add($guest);

        // роль сотрудника
        $user = $this->auth->createRole('user');
        $this->auth->add($user);

        // роль админа
        $admin = $this->auth->createRole('admin');
        $this->auth->add($admin);

        $this->auth->addChild($user, $guest);

        $this->auth->addChild($admin, $user);
        parent::init();
    }

    public function actionIndex()
    {
        $auth = $this->auth;
        /** @var Role $role */
        $role = $auth->getRole('user');

        echo 'success';
    }

}
