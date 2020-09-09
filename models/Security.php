<?php

namespace wdmg\guard\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use wdmg\helpers\IpAddressHelper;
use yii\web\HttpException;
use yii\web\Request;

class Security extends \yii\db\ActiveRecord
{

    const GUARD_STATUS_IS_BANNED = 1;
    const GUARD_STATUS_IS_UNBANNED = 2;
    const GUARD_STATUS_IS_RELEASED = 3;

    private $_module;
    private $useIpRange;
    private $maxAttempts;
    private $releaseTime;
    private $attemptsDuration;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->_module = self::getModule(true);

        if (isset(Yii::$app->params["guard.maxAttempts"]))
            $this->maxAttempts = Yii::$app->params["guard.maxAttempts"];
        elseif (isset($this->_module->maxAttempts))
            $this->maxAttempts = $this->_module->maxAttempts;
        else
            $this->maxAttempts = 5;

        if (isset(Yii::$app->params["guard.attemptsDuration"]))
            $this->attemptsDuration = Yii::$app->params["guard.attemptsDuration"];
        elseif (isset($this->_module->attemptsDuration))
            $this->attemptsDuration = $this->_module->attemptsDuration;
        else
            $this->attemptsDuration = 3600;

        if (isset(Yii::$app->params["guard.releaseTime"]))
            $this->releaseTime = Yii::$app->params["guard.releaseTime"] . " seconds";
        elseif (isset($this->_module->releaseTime))
            $this->releaseTime = $this->_module->releaseTime . " seconds";
        else
            $this->releaseTime = '1 hour';

        if (isset(Yii::$app->params["guard.useIpRange"]))
            $this->useIpRange = Yii::$app->params["guard.useIpRange"];
        elseif (isset($this->_module->useIpRange))
            $this->useIpRange = $this->_module->useIpRange;
        else
            $this->useIpRange = true;

    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%guard_banned}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function() {
                    return date("Y-m-d H:i:s");
                }
            ],
            'blameable' =>  [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['client_ip', 'status'], 'required'],
            [['client_ip', 'range_start', 'range_end'], 'integer', 'min' => ip2long('0.0.0.0'), 'max' => ip2long('255.255.255.255')],
            ['status', 'integer', 'max' => 1],
            ['reason', 'in', 'range' => array_keys($this->getReasonsList(false))],
            [['client_net', 'user_agent'], 'string', 'max' => 255],
            [['created_at', 'updated_at', 'release_at'], 'safe'],
        ];

        if (class_exists('\wdmg\users\models\Users') && (Yii::$app->hasModule('admin/users') || Yii::$app->hasModule('users'))) {
            $rules[] = [['created_by', 'updated_by'], 'safe'];
        }

        return ArrayHelper::merge(parent::rules(), $rules);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $rules = [
            'id' => Yii::t('app/modules/guard', 'ID'),
            'client_ip' => Yii::t('app/modules/guard', 'Client IP'),
            'client_net' => Yii::t('app/modules/guard', 'Client Net'),
            'range_start' => Yii::t('app/modules/guard', 'IP range (start)'),
            'range_end' => Yii::t('app/modules/guard', 'IP range (end)'),
            'user_agent' => Yii::t('app/modules/guard', 'User Agent'),
            'status' => Yii::t('app/modules/guard', 'Status'),
            'reason' => Yii::t('app/modules/guard', 'Reason'),
            'created_at' => Yii::t('app/modules/guard', 'Created'),
            'created_by' => Yii::t('app/modules/guard', 'Created by'),
            'updated_at' => Yii::t('app/modules/guard', 'Updated'),
            'updated_by' => Yii::t('app/modules/guard', 'Updated by'),
            'release_at' => Yii::t('app/modules/guard', 'Release'),
        ];

        if (class_exists('\wdmg\users\models\Users') && (Yii::$app->hasModule('admin/users') || Yii::$app->hasModule('users'))) {
            $rules[] = [['created_by', 'updated_by'], 'safe'];
        }

        return ArrayHelper::merge(parent::rules(), $rules);
    }

    /**
     * Blocking current IP and subnet
     *
     * @param $ip
     * @param $user_agent
     * @param $reason
     */
    protected function setBanned($ip, $user_agent, $reason)
    {
        if ((IpAddressHelper::ipVersion($ip) == "IPv4" && !IpAddressHelper::isLocalIp($ip))) {

            $this->client_ip = ip2long($ip);

            if ($this->useIpRange && $cidr = IpAddressHelper::ip2cidr($ip, 1)) {
                $this->client_net = $cidr;
                if ($range = IpAddressHelper::cidr2range($cidr, true)) {
                    $this->range_start = ip2long($range->start);
                    $this->range_end = ip2long($range->end);
                }
            }

            $this->user_agent = $user_agent;
            $this->status = self::GUARD_STATUS_IS_BANNED;
            $this->reason = ($reason) ? ($reason) : null;
            $this->release_at = $this->getReleaseDate();

            // Add new banned client
            if ($this->validate()) {
                $this->save();
            }
        }
    }

    /**
     * Checks if the given IP or its subnet is blocked
     *
     * @param $ip
     * @return bool|int|string|null
     */
    protected function getHasBanned($ip)
    {
        $banned = null;
        if ((IpAddressHelper::ipVersion($ip) == "IPv4" && !IpAddressHelper::isLocalIp($ip))) {

            $query = self::find();
            $query->where(['status' => self::GUARD_STATUS_IS_BANNED]);
            $query->andWhere(['>=', 'release_at', date('Y-m-d H:i:s')]);
            $query->andWhere(['client_ip' => ip2long($ip)]);

            if ($this->useIpRange && $cidr = IpAddressHelper::ip2cidr($ip, 1)) {
                if ($range = IpAddressHelper::cidr2range($cidr, true)) {
                    $this->range_start = ip2long($range->start);
                    $this->range_end = ip2long($range->end);
                    $query->orWhere(['and',
                        ['>', 'range_start', ip2long($range->start)],
                        ['<', 'range_end', ip2long($range->end)]
                    ]);
                }
            }

            $banned = $query->count();
            return $banned;
        }

        return false;
    }

    /**
     * Blocking attack and throws a special exception
     *
     * @param null $reason
     * @param string $message
     * @throws HttpException
     */
    public function setBlock($reason = null, $message = '')
    {
        // Get request
        $request = Yii::$app->getRequest();
        $ip = $this->getRemoteIp($request);
        $user_agent = $request->userAgent;

        if ($reason && $this->getNeedBann($ip))
            $this->setBanned($ip, $user_agent, $reason);

        if ($reason == 'ratelimit')
            throw new HttpException('429', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'Rate limit exceeded.'));

        if ($reason == 'overdrive')
            throw new HttpException('403', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'Overdrive attack detected.'));

        if ($reason == 'xss')
            throw new HttpException('403', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'XSS-attack detected.'));

        if ($reason == 'lfi')
            throw new HttpException('403', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'LFI/RFI/RCE attack detected.'));

        if ($reason == 'php')
            throw new HttpException('403', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'PHP-injection detected.'));

        if ($reason == 'sql')
            throw new HttpException('403', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'SQL-injection detected.'));

        throw new HttpException('403', (!empty($message)) ? $message : Yii::t('app/modules/guard', 'Access denied from security reason.'));
        exit;
    }

    /**
     * Checks if IP or network is blocked and throws an appropriate exception
     *
     * @throws HttpException
     */
    public function getBlock()
    {
        // Get request
        $request = Yii::$app->getRequest();
        $ip = $this->getRemoteIp($request);
        if ($this->getHasBanned($ip)) {
            $this->setBlock(null);
        }
    }

    /**
     * Get client IP
     *
     * @param $request Request
     * @return string or null
     */
    public static function getRemoteIp($request)
    {
        $client_ip = $request->userIP;
        if(!$client_ip)
            $client_ip = $request->remoteIP;

        return $client_ip;
    }

    /**
     * Checks if user activity is one of the attack types
     *
     * @param $filters
     * @param $type
     * @param $params
     * @return bool
     */
    public static function checkAttack($filters, $type, $params) {
        foreach ($params as $param => $value) {

            if (is_array($value))
                return self::checkAttack($filters, $type, $value);

            if (!(preg_match($filters[$type], $value) === 0))
                return true;

        }
        return false;
    }

    /**
     * Returns an array of blocking list statuses.
     *
     * @param bool $allStatuses
     * @return array
     */
    public function getStatusesList($allStatuses = false)
    {
        if ($allStatuses)
            $list[] = [
                '*' => Yii::t('app/modules/guard', 'All statuses')
            ];

        $list[] = [
            self::GUARD_STATUS_IS_BANNED => Yii::t('app/modules/guard', 'Banned'),
            self::GUARD_STATUS_IS_UNBANNED => Yii::t('app/modules/guard', 'Unbanned'),
            self::GUARD_STATUS_IS_RELEASED => Yii::t('app/modules/guard', 'Released')
        ];

        return $list;
    }

    /**
     * Returns an array of blocking reasons list.
     *
     * @param bool $allStatuses
     * @return array
     */
    public function getReasonsList($allReasons = false)
    {
        if ($allReasons)
            $list[] = [
                '*' => Yii::t('app/modules/guard', 'All reasons')
            ];

        $list[] = [
            'manual' => Yii::t('app/modules/guard', 'Manual blocking'),
            'ratelimit' => Yii::t('app/modules/guard', 'Rate limit'),
            'overdrive' => Yii::t('app/modules/guard', 'Overdrive attack'),
            'xss' => Yii::t('app/modules/guard', 'XSS-attack'),
            'lfi' => Yii::t('app/modules/guard', 'LFI/RFI/RCE attack'),
            'php' => Yii::t('app/modules/guard', 'PHP-injection'),
            'sql' => Yii::t('app/modules/guard', 'SQL-injection')
        ];

        return $list;
    }

    /**
     * Checks if blocking is necessary based on IP and number of attack attempts.
     * Also increases the counter of the number of attack attempts from this IP.
     *
     * @param $ip
     * @return bool
     */
    private function getNeedBann($ip)
    {
        if ($cache = Yii::$app->getCache()) {

            $current = $cache->get(['guard', 'ip' => $ip]);

            if ($current)
                $attempts = (int)$current + 1;
            else
                $attempts = 1;

            $cache->set(['guard', 'ip' => $ip], $attempts, $this->attemptsDuration);

            if ($attempts >= $this->maxAttempts)
                return true;
            else
                return false;

        } else {
            return true;
        }
    }

    /**
     * @return object of \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        if (class_exists('\wdmg\users\models\Users'))
            return $this->hasOne(\wdmg\users\models\Users::class, ['id' => 'created_by']);
        else
            return $this->created_by;
    }

    /**
     * @return object of \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        if (class_exists('\wdmg\users\models\Users'))
            return $this->hasOne(\wdmg\users\models\Users::class, ['id' => 'updated_by']);
        else
            return $this->updated_by;
    }

    /**
     * Returns the date / time the restrictions were lifted after blocking.
     *
     * @return false|string
     */
    private function getReleaseDate()
    {
        $release = strtotime("+ " . trim($this->releaseTime), strtotime(date('Y-m-d H:i:s')));
        return date('Y-m-d H:i:s', $release);
    }

    /**
     * Returns the instance (or id) of parent Module of current model
     *
     * @param bool $instance
     * @return object|null
     */
    private function getModule($instance = false)
    {
        if ($instance)
            return (is_object($this->_module)) ? $this->_module : null;

        return (isset($this->_module->id)) ? $this->_module->id : null;
    }
}