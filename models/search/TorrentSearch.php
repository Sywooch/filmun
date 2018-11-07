<?php
namespace app\models\search;

use Yii;
use app\models\Torrent;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class TorrentSearch extends Torrent
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
            [['title', 'size_text', 'quality_text', 'transfer_text', 'created_at'], 'safe'],
        ];
    }

    public function search($params = [])
    {
        $query = Torrent::find();
        $query->from(['t' => self::tableName()]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->setPagination([
            'defaultPageSize' => 50
        ]);

        $this->load($params);

        $query->andFilterWhere(['like', 't.title', $this->title]);
        $query->andFilterWhere(['like', 't.size_text', $this->size_text]);
        $query->andFilterWhere(['like', 't.quality_text', $this->quality_text]);
        $query->andFilterWhere(['like', 't.transfer_text', $this->transfer_text]);
        //$query->andFilterWhere(['like', 't.created_at', $this->created_at]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'created_at' => SORT_DESC,
            ],
        ]);

        return $dataProvider;
    }
}