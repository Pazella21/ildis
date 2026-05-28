<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FooterLink;

class FooterLinkSearch extends FooterLink
{
    public function rules()
    {
        return [
            [['id', 'section_id', 'sort_order', 'status', 'open_in_new_tab'], 'integer'],
            [['label', 'url', 'icon_class'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = FooterLink::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['section_id' => SORT_ASC, 'sort_order' => SORT_ASC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'section_id' => $this->section_id,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'open_in_new_tab' => $this->open_in_new_tab,
        ]);

        $query->andFilterWhere(['like', 'label', $this->label])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'icon_class', $this->icon_class]);

        return $dataProvider;
    }
}