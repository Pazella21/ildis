<?php

namespace backend\models;

use common\models\SurveyKepuasan;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class SurveyKepuasanSearch extends SurveyKepuasan
{
    public function rules(): array
    {
        return [
            [['id', 'tingkat_kepuasan'], 'integer'],
            [['ip_address', 'isi', 'created_at'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = SurveyKepuasan::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'tingkat_kepuasan' => $this->tingkat_kepuasan,
        ]);
        $query->andFilterWhere(['like', 'ip_address', $this->ip_address])
            ->andFilterWhere(['like', 'isi', $this->isi])
            ->andFilterWhere(['like', 'created_at', $this->created_at]);

        return $dataProvider;
    }
}
