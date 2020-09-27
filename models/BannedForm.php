<?php

namespace wdmg\guard\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use wdmg\guard\models\Security;
use wdmg\helpers\ArrayHelper;
use wdmg\helpers\IpAddressHelper;

/**
 * BannedForm represents the model `app\vendor\wdmg\guard\models\Security`.
 */
class BannedForm extends Security
{
    const SCENARIO_ADD = 'add';
    const SCENARIO_TEST = 'test';
    const GUARD_STATUS_IS_DELETED = 4;

    public $ip = '';
    public $reason = 'manual';
    public $client_net = '';
    public $user_agent = '';

    private $_ip;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['ip', 'status'], 'required', 'on' => self::SCENARIO_ADD],
            [['ip'], 'required', 'on' => self::SCENARIO_TEST],
            ['ip', 'trim'],
            ['status', 'integer', 'on' => self::SCENARIO_ADD],
            ['ip', 'maxCount'],
            ['ip', 'each', 'rule' => ['ip', 'subnet' => null, 'ranges' => ['!system', '!private', 'any'], 'ipv6' => false], 'skipOnEmpty' => false, 'on' => self::SCENARIO_ADD],
            ['ip', 'each', 'rule' => ['ip', 'subnet' => false, 'ranges' => ['!system', '!private', 'any'], 'ipv6' => false], 'skipOnEmpty' => false, 'on' => self::SCENARIO_TEST],
            ['release_at', 'in', 'range' => array_keys($this->getReleases())],
        ];

        return ArrayHelper::merge(parent::rules(), $rules);
    }

    public function maxCount($attribute, $params) {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Yii::t('app/modules/guard', 'The `{attribute}` attribute must be an array list.', [
                'attribute' => $this->getAttributeLabel($attribute)
            ]));
        } else {
            if (count($this->$attribute) > 100) {
                $this->addError($attribute, Yii::t('app/modules/guard', 'The `{attribute}` list must not exceed 100 items.', [
                    'attribute' => $this->getAttributeLabel($attribute)
                ]));
            }
        }
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
            $this->_ip = $this->ip;

            $ips = [];
            foreach ($this->ip as $ip) {
                if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $ip, $mathes)) {
                    $ips[] = trim($mathes[1]);
                    $ips[] = trim($mathes[2]);
                } elseif (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\-(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $ip, $mathes)) {
                    $ips[] = trim($mathes[1]);
                    $ips[] = trim($mathes[2]);
                } else {
                    $ips[] = $ip;
                }
            }
            $this->ip = $ips;
        }

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function afterValidate()
    {
        if (is_array($this->ip)) {
            $this->ip = implode("\r\n", $this->_ip);
        }
        parent::afterValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $labels = [
            'ip' => Yii::t('app/modules/guard', 'IP or/and Net'),
            'release_at' => Yii::t('app/modules/guard', 'Blocking period')
        ];
        return ArrayHelper::merge(parent::attributeLabels(), $labels);
    }

    /**
     * {@inheritdoc}
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $count = 0;
        $errors = [];

        // Prepare data for insert
        $data = $this->prepare($this->_ip);

        // Batch insert
        foreach ($data as $item) {

            $skip = false;
            $user_ip = parent::getRemoteIp();
            if (IpAddressHelper::isIpv4($user_ip)) {
                if (isset($item['client_ip'])) {
                    if ($user_ip == $item['client_ip']) {
                        $errors[] = Yii::t('app/modules/guard', 'It looks like your IP matches the blocked `{ip}` and cannot be blocked.', [
                            'ip' => $item['client_ip']
                        ]);
                        $skip = true;
                    }
                }

                if (!$skip && isset($item['client_net'])) {
                    if (IpAddressHelper::ipInCidr($user_ip, $item['client_net'])) {
                        $errors[] = Yii::t('app/modules/guard', 'It looks like your IP belongs to the blocked `{subnet}` subnet and cannot be blocked.', [
                            'subnet' => $item['client_net']
                        ]);
                        $skip = true;
                    }
                }

                if (!$skip && isset($item['range_start']) && isset($item['range_end'])) {
                    if (IpAddressHelper::ipInRange($user_ip, $item['range_start'], $item['range_end'])) {
                        $errors[] = Yii::t('app/modules/guard', 'It looks like your IP is in the blocking range `{start} - {end}` and cannot be blocked.', [
                            'start' => $item['range_start'],
                            'end' => $item['range_end']
                        ]);
                        $skip = true;
                    }
                }
            }

            if ($skip) {
                continue;
            } else {
                $security = new Security();
                $security->reason = $this->reason;
                $security->status = $this->status;
                $security->client_ip = ($item['client_ip']) ? $item['client_ip'] : null;
                $security->client_net = ($item['client_net']) ? $item['client_net'] : null;
                $security->range_start = ($item['range_start']) ? $item['range_start'] : null;
                $security->range_end = ($item['range_end']) ? $item['range_end'] : null;

                if (is_string($this->release_at) && $this->release_at !== 'default')
                    $security->release_at = $security->getReleaseDate(str_replace('_', ' ', $this->release_at));

                if ($security->validate()) {
                    if ($security->save()) {
                        $count++;
                    }
                }
            }

            $errors = array_merge($security->errors, $errors);
        }

        return [
            'errors' => $errors,
            'count' => $count
        ];
    }


    public function test() {

        $results = [];

        // Prepare data for test
        $data = $this->prepare($this->_ip);

        foreach ($data as $item) {
            $banned = false;
            if (isset($item['client_ip'])) {
                if ($this->getHasBanned($item['client_ip']))
                    $results[$item['client_ip']] = parent::GUARD_STATUS_IS_BANNED;
                else
                    $results[$item['client_ip']] = null;
            }
        }

        return $results;
    }

    private function prepare($ips) {
        $data = [];
        foreach ($ips as $key => $ip) {
            if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $ip, $mathes)) { // 172.104.89.0/255.255.255.0
                if ($cidr = IpAddressHelper::ip2BaseCidr(trim($mathes[1]), trim($mathes[2]))) {
                    if ($range = IpAddressHelper::cidr2range($cidr, null)) {
                        $data[] = [
                            'client_ip' => null,
                            'client_net' => $cidr,
                            'range_start' => $range[0],
                            'range_end' => $range[1]
                        ];
                    }
                }
            } elseif (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\-(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $ip, $mathes)) { // 172.104.89.0-172.104.89.255
                $cidrs = IpAddressHelper::range2cidrs(trim($mathes[1]), trim($mathes[2]));
                foreach ($cidrs as $cidr) {
                    $range = IpAddressHelper::cidr2range($cidr, null);
                    $data[] = [
                        'client_ip' => null,
                        'client_net' => $cidr,
                        'range_start' => $range[0],
                        'range_end' => $range[1]
                    ];
                }
            } elseif (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})/", $ip, $mathes)) { // 172.104.89.12/24
                $cidr = trim($mathes[0]);
                if (IpAddressHelper::isValidCidr($cidr)) {
                    if ($range = IpAddressHelper::cidr2range($cidr, null)) {
                        $data[] = [
                            'client_ip' => null,
                            'client_net' => $cidr,
                            'range_start' => $range[0],
                            'range_end' => $range[1]
                        ];
                    }
                }
            } else if (IpAddressHelper::getIpVersion($ip, true) == "IPv4" && !IpAddressHelper::isLocalIp($ip)) {
                $item = [];
                $item['client_ip'] = $ip;
                if ($netmask = IpAddressHelper::ipMask($ip, false)) {
                    $cidr = IpAddressHelper::ip2cidr($ip, $netmask, 2);
                    if (!$cidr)
                        $cidr = IpAddressHelper::ip2cidr($ip, $netmask, 1);

                    if ($cidr) {
                        $item['client_net'] = $cidr;
                        if ($range = IpAddressHelper::cidr2range($cidr, 1)) {
                            $item['range_start'] = $range->start;
                            $item['range_end'] = $range->end;
                        }
                    }
                }
                $data[] = $item;
            } else {
                continue;
            }
        }

        return ArrayHelper::unique($data, ['client_ip', 'range_start', 'range_end']);
    }

    /**
     * Returns an array of blocking list statuses.
     *
     * @return array
     */
    public function getStatuses($addDelete = false)
    {
        $list = [
            self::GUARD_STATUS_IS_BANNED => Yii::t('app/modules/guard', 'Ban'),
            self::GUARD_STATUS_IS_UNBANNED => Yii::t('app/modules/guard', 'Unban'),
            self::GUARD_STATUS_IS_RELEASED => Yii::t('app/modules/guard', 'Release')
        ];

        if ($addDelete)
            $list[self::GUARD_STATUS_IS_DELETED] = Yii::t('app/modules/guard', 'Delete');

        return $list;
    }

    /**
     * Returns an array of blocking list statuses.
     *
     * @return array
     */
    public function getReleases()
    {
        return [
            'default' => Yii::t('app/modules/guard', 'Default'),
            '1_hour' => Yii::t('app/modules/guard', '1 hour'),
            '6_hours' => Yii::t('app/modules/guard', '6 hours'),
            '1_day' => Yii::t('app/modules/guard', '1 day'),
            '1_week' => Yii::t('app/modules/guard', '1 week'),
            '2_weeks' => Yii::t('app/modules/guard', '2 weeks'),
            '1_month' => Yii::t('app/modules/guard', '1 month'),
            '6_months' => Yii::t('app/modules/guard', '6 months'),
            '1_year' => Yii::t('app/modules/guard', '1 year'),
            'lifetime' => Yii::t('app/modules/guard', 'Lifetime')
        ];
    }
}