<?php

namespace wdmg\guard\models;

use wdmg\helpers\IpAddressHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use wdmg\guard\models\Security;

/**
 * SecuritySearch represents the model behind the search form of `app\vendor\wdmg\guard\models\Security`.
 */
class SecuritySearch extends Security
{

    public $contents;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'integer'],
            [['client_ip', 'client_net', 'user_agent', 'user_agent', 'reason'], 'string'],
            [['created_at', 'created_by', 'updated_at', 'updated_by', 'release_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Security::find()->alias('banned');

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
            'release_at' => $this->release_at,
        ]);

        if ($this->client_ip)
            $query->andFilterWhere(['client_ip' => ip2long(trim($this->client_ip))]);

        if ($this->client_net) {
            if ($range = IpAddressHelper::cidr2range($this->client_net, true)) {
                $this->range_start = ip2long($range->start);
                $this->range_end = ip2long($range->end);
                $query->orWhere(['and',
                    ['>', 'range_start', ip2long($range->start)],
                    ['<', 'range_end', ip2long($range->end)]
                ]);
            }
        }

        if ($this->user_agent)
            $query->andFilterWhere(['like', 'user_agent', $this->user_agent]);

        if ($this->status !== "*")
            $query->andFilterWhere(['like', 'status', $this->status]);
        
        if ($this->reason !== "*")
            $query->andFilterWhere(['like', 'reason', $this->status]);


        return $dataProvider;
    }
}
