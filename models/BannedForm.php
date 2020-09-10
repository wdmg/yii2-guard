<?php

namespace wdmg\guard\models;

use wdmg\helpers\IpAddressHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use wdmg\guard\models\Security;
use yii\helpers\ArrayHelper;
use yii\validators\IpValidator;

/**
 * BannedForm represents the model `app\vendor\wdmg\guard\models\Security`.
 */
class BannedForm extends Security
{
    public $ip;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['ip', 'status'], 'required'],
            ['ip', 'trim'],
            ['ip', 'each', 'rule' => ['ip', 'subnet' => null, 'ranges' => ['!system', '!private', 'any']], 'skipOnEmpty' => false]
        ];
        return ArrayHelper::merge(parent::rules(), $rules);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        $this->reason = 'manual';

        if (is_string($this->ip)) {
            $list = explode("\r\n", $this->ip);
            $this->ip = array_filter(array_unique($list), 'trim');
        }

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function afterValidate()
    {
        if (is_array($this->ip)) {
            $this->ip = implode("\r\n", $this->ip);
        }
        parent::afterValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $labels = [
            'ip' => Yii::t('app/modules/guard', 'IP or/and Net')
        ];
        return ArrayHelper::merge(parent::attributeLabels(), $labels);
    }

    /**
     * Returns an array of blocking list statuses.
     *
     * @return array
     */
    public function getStatuses()
    {
        return [
            self::GUARD_STATUS_IS_BANNED => Yii::t('app/modules/guard', 'Ban'),
            self::GUARD_STATUS_IS_UNBANNED => Yii::t('app/modules/guard', 'Unban'),
            self::GUARD_STATUS_IS_RELEASED => Yii::t('app/modules/guard', 'Release')
        ];
    }
}