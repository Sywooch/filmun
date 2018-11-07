<?php
namespace app\behaviors;

use app\models\ActiveRecord;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 *
 * @property ActiveRecord $owner
 */
class TreeBehavior extends Behavior
{
    public $parentAttribute = 'parent_id';

    public $parentsAttribute = 'parent_ids';

    public $levelAttribute = 'level';

    public $filter;

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getChildren()
    {
        return $this->owner->hasMany($this->owner->className(), [$this->parentAttribute => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getAllChildren()
    {
        /* @var ActiveQuery $query */
        $class = $this->owner->className();
        $query = $class::find();
        $query->andWhere("FIND_IN_SET(:id, [[{$this->parentsAttribute}]])", [':id' => $this->owner->id]);
        $query->multiple = true;
        return $query;
    }

    /**
     * @return ActiveQuery
     */
    public function getParent()
    {
        return $this->owner->hasOne($this->owner->className(), ['id' => $this->parentAttribute]);
    }

    /**
     * @return ActiveQuery
     */
    public function getRootParent()
    {
        $query = $this->getParents()->andWhere([$this->levelAttribute => 1]);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return ActiveQuery
     */
    public function getParents()
    {
        $owner = $this->owner;
        $ids = ArrayHelper::getValue($owner, $this->parentsAttribute);
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $query = $owner::find();
        $query->multiple = true;
        $query->andWhere(['id' => $ids]);
        $query->orderBy([$this->levelAttribute => SORT_ASC]);
        return $query;
    }

    /**
     * @return array
     */
    public function getParentList()
    {
        $owner = $this->owner;
        $query = $owner::find();
        if($this->filter instanceof \Closure) {
            $filter = $this->filter;
            $filter($query);
        }
        $query->andWhere('[[' . $this->parentAttribute . ']] IS NULL');
        if(!$owner->isNewRecord) {
            $query->andWhere(['!=', 'id', $owner->id]);
        }
        $list = [];
        foreach($query->all() as $model) {
            $list[$model->id] = $model->name;
            $list = ArrayHelper::merge($list, $this->getChildrenListRecursive($model));
        }
        return $list;
    }

    /**
     * @return array
     */
    protected function getChildrenListRecursive($parent)
    {
        /* @var ActiveQuery $query */
        $owner = $this->owner;
        $query = $parent->getChildren();
        if(!$owner->isNewRecord) {
            $query->andWhere(['!=', 'id', $owner->id]);
        }
        $list = [];
        foreach($query->all() as $model) {
            $list[$model->id] = str_repeat(' - ', $model->level) . $model->name;
            $list = ArrayHelper::merge($list, $this->getChildrenListRecursive($model));
        }
        return $list;
    }

    public function beforeSave()
    {
        $owner = $this->owner;
        $parent = $owner;
        $parent_ids = [];
        while($parent = $parent->parent) {
            $parent_ids[] = $parent->id;
        }
        $owner->setAttribute($this->parentsAttribute, implode(',', $parent_ids));

        $level = ArrayHelper::getValue($owner->parent, $this->levelAttribute, 0);
        $owner->setAttribute($this->levelAttribute, $level + 1);
    }

    public function beforeDelete()
    {
        foreach($this->getChildren()->all() as $child) {
            $child->delete();
        }
    }

    public function afterSave()
    {
        foreach($this->getChildren()->all() as $child) {
            $child->save(false);
        }
    }
}
