<?php
namespace app\models\search;

use Yii;
use app\models\TorrentCatalog;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

class TorrentCatalogSearch extends TorrentCatalog
{
    public function formName()
    {
        return '';
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'tracker', 'count_till_week', 'count_till_month', 'count_total', 'count_errors', 'success_percent', 'is_series', 'check_interval'], 'safe'],
        ];
    }

    public function search($params = [])
    {
        $query = TorrentCatalog::find();
        $query->from(['t' => self::tableName()]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->setPagination([
            'defaultPageSize' => 50
        ]);

        $this->load($params);

        foreach(['count_till_week', 'count_till_month', 'count_total', 'count_errors', 'success_percent', 'check_interval'] as $attr) {
            $value = $this->{$attr};
            if(empty($value)) {
                continue;
            }
            $cond = '=';
            if(StringHelper::startsWith($value, '=')) {
                $cond = '=';
                $value = substr($value, 1);
            }
            if(StringHelper::startsWith($value, '>=')) {
                $cond = '>=';
                $value = substr($value, 2);
            }
            if(StringHelper::startsWith($value, '<=')) {
                $cond = '<=';
                $value = substr($value, 2);
            }
            if(StringHelper::startsWith($value, '>')) {
                $cond = '>';
                $value = substr($value, 1);
            }
            if(StringHelper::startsWith($value, '<')) {
                $cond = '<';
                $value = substr($value, 1);
            }
            $query->andFilterWhere([$cond, 't.' . $attr, (int)$value]);
        }

        if($this->is_series === '0' || $this->is_series === '1') {
            $query->andFilterWhere(['like', 't.is_series', $this->is_series]);
        }

        $query->andFilterWhere(['like', 't.name', $this->name]);
        $query->andFilterWhere([
            'tracker' => $this->tracker
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'name' => SORT_DESC,
            ],
        ]);

        return $dataProvider;
    }
}