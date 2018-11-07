<?php
namespace app\models;

use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\validators\RequiredValidator;

class ActiveRecord extends \yii\db\ActiveRecord
{
    private static $_ownList = [];

    /**
     * @return string
     */
    public static function nameAttribute()
    {
        return 'name';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->{self::nameAttribute()};
    }

    /**
     * @return array
     */
    public function typeArrayAttributes()
    {
        return [];
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function isAttributeArray($attribute)
    {
        return in_array($attribute, $this->typeArrayAttributes());
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        $value = parent::__get($name);
        if ($this->isAttributeArray($name)) {
            $value = $value ? explode(',', $value) : [];
        }
        return $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $attr = self::nameAttribute();
        return $this->{$attr};
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if($this->isAttributeArray($name)) {
            $value = is_array($value) ? implode(',', $value) : $value;
        }
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($name)
    {
        $value = parent::getAttribute($name);
        if ($this->isAttributeArray($name)) {
            $value = $value ? explode(',', $value) : [];
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values)) {
            $attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
            foreach ($values as $name => $value) {
                if($this->isAttributeArray($name)) {
                    if(empty($value)) {
                        $value = [];
                    } else if(!is_array($value)) {
                        $value = explode(',', $value);
                    }
                }
                if (isset($attributes[$name])) {
                    $this->$name = $value;
                } elseif ($safeOnly) {
                    $this->onUnsafeAttribute($name, $value);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getOldAttribute($name)
    {
        $value = parent::getOldAttribute($name);
        if ($this->isAttributeArray($name)) {
            $value = $value ? explode(',', $value) : [];
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function setAttribute($name, $value)
    {
        if($this->isAttributeArray($name)) {
            $value = is_array($value) ? implode(',', $value) : $value;
        }
        parent::setAttribute($name, $value);
    }

    /**
     * @param $attribute
     * @return string
     */
    public function getListMethod($attribute)
    {
        $name = $attribute;
        if(substr($attribute, -3) == '_id') {
            $name = substr($attribute, 0, -3);
        }
        if(substr($attribute, -5) == '_from') {
            $name = substr($attribute, 0, -5);
        }
        if(substr($attribute, -5) == '_till') {
            $name = substr($attribute, 0, -5);
        }
        return 'get' . Inflector::id2camel($name, '_') . 'List';
    }

    /**
     * @param $attribute
     * @return array
     * @throws \yii\base\Exception
     */
    public function getList($attribute)
    {
        $method = $this->getListMethod($attribute);
        if (!method_exists($this, $method))
            throw new Exception('Отсутствует метод ' . $method);
        return $this->$method();
    }

    /**
     * @param $attribute
     * @return array
     */
    public function getListKeys($attribute)
    {
        return array_keys($this->getList($attribute));
    }

    /**
     * @param $attribute
     * @param $values
     * @return string
     */
    public function getBeautyValue($attribute, $values)
    {
        is_array($values) or $values = [$values];
        $method = $this->getListMethod($attribute);
        if(method_exists($this, $method)) {
            $list = $this->$method();
            foreach($list as $key => $val) { // если список вложенный
                if(is_array($val)) {
                    unset($list[$key]);
                    foreach($val as $key2 => $val2) {
                        $list[$key2] = $val2;
                    }
                }
            }
            foreach($values as $key => $value) {
                $values[$key] = ArrayHelper::getValue($list, $value);
            }
        }
        return implode(', ', $values);
    }

    /**
     * @param $attribute
     * @param string $default
     * @return string
     * @throws \yii\base\Exception
     */
    public function getBeauty($attribute, $default = '')
    {
        if (!$this->hasAttribute($attribute))
            throw new Exception('Отсутствует атрибут' . ' ' . $attribute);

        $value = $this->getAttribute($attribute);
        return empty($value) ? $default : $this->getBeautyValue($attribute, $value);
    }

    /**
     * @param array $a array to be merged to
     * @param array $b array to be merged from
     * @return array the merged array
     */
    public function mergeAttributes($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $attr) {
                if ($attr[0] === '-') {
                    $attr = substr($attr, 1);
                    $search = array_search($attr, $res);
                    if($search !== false) {
                        unset($res[$search]);
                    }
                } else {
                    $res[] = $attr;
                }
            }
        }
        return array_values($res);
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function isAttributesSafe(array $attributes)
    {
        $safe = false;
        foreach($attributes as $attribute) {
            $safe = $safe || $this->isAttributeSafe($attribute);
        }
        return $safe;
    }

    /**
     * @param $attributes
     * @return array
     */
    public function getAttributeLabels($attributes)
    {
        $labels = [];
        foreach($attributes as $attribute) {
            $labels[] = $this->getAttributeLabel($attribute);
        }
        return $labels;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function isAttributeRequired($attribute)
    {
        foreach ($this->getActiveValidators($attribute) as $validator) {
            if ($validator instanceof RequiredValidator) {
                $when = $validator->when;
                if($when === null || $when($this)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function isAttributesRequired(array $attributes)
    {
        $required = false;
        foreach($attributes as $attribute) {
            $required = $required || $this->isAttributeRequired($attribute);
        }
        return $required;
    }

    /**
     * @return array
     */
    public static function ownList()
    {
        $key = static::className();
        if(!array_key_exists($key, self::$_ownList)) {
            self::$_ownList[$key] = ArrayHelper::map(static::find()->all(), static::primaryKey(), static::nameAttribute());
        }
        return self::$_ownList[$key];
    }
}