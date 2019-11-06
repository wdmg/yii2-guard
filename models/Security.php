<?php

namespace wdmg\guard\models;

use Yii;
use yii\web\HttpException;

class Security extends yii\base\Model
{

    public function rules()
    {
        return [
        ];
    }

    public function attributeLabels()
    {
        return [
        ];
    }


    public function setBanned()
    {

    }

    public function setBlock($reason = null, $message = '')
    {
        if ($reason == 'ratelimit')
            throw new HttpException('429', (!empty($message)) ? $message : 'Rate limit exceeded.');

        if ($reason == 'overdrive')
            throw new HttpException('403', (!empty($message)) ? $message : 'Overdrive attack detected.');

        if ($reason == 'xss')
            throw new HttpException('403', (!empty($message)) ? $message : 'XSS-attack detected.');

        if ($reason == 'lfi')
            throw new HttpException('403', (!empty($message)) ? $message : 'LFI/RFI/RCE attack detected.');

        if ($reason == 'php')
            throw new HttpException('403', (!empty($message)) ? $message : 'PHP-injection detected.');

        if ($reason == 'sql')
            throw new HttpException('403', (!empty($message)) ? $message : 'SQL-injection detected.');

        throw new HttpException('403', (!empty($message)) ? $message : 'Access denied from security reason.');
    }

    /**
     * Simply mask of IP
     * @param $ip
     * @return string
     */
    public static function ipMask($ip)
    {
        if ($mask = explode('.', $ip))
            return $mask[0].'.'.$mask[1].'.'.$mask[2].'.0';
        else
            return $ip;
    }

    public static function checkAttack($filters, $type, $params) {
        foreach ($params as $param => $value) {

            if (is_array($value))
                return self::checkAttack($filters, $type, $value);

            if (!(preg_match($filters[$type], $value) === 0))
                return true;

        }
        return false;
    }

}