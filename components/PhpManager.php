<?php
namespace app\components;

use Yii;
use app\models\User;
use yii\rbac;

class PhpManager extends rbac\PhpManager
{
    /**
     * @inheritdoc
     */
    public function getAssignments($userId)
    {
        if(!isset($this->assignments[$userId])) {
            /* @var User $user */
            $user = User::findIdentity($userId);
            if($user) {
                $assignment = new rbac\Assignment;
                $assignment->userId = $userId;
                $assignment->roleName = $user->role;
                $this->assignments[$userId] = [$assignment->roleName => $assignment];
            } else {
                $this->assignments[$userId] = [];
            }
        }
        return $this->assignments[$userId];
    }
}
