<?php

namespace wdmg\guard\behaviors;

use Yii;
use yii\base\Application;
use yii\base\Behavior;

class RequestBehavior extends Behavior
{

    /**
     * Base Guard Module
     */
    public $module;

    /**
     * Base Security Model
     */
    public $security;

    public function events()
    {
        return [
            Application::EVENT_BEFORE_REQUEST => 'onBeforeRequest',
        ];
    }

    public function onBeforeRequest($event)
    {

        $this->security->getBlock();

        $post = Yii::$app->getRequest()->post();
        $get = Yii::$app->getRequest()->get();

        if ($this->module->useOverdriveLimit) {

            if (isset($this->module->overdriveLimit['post'])) {
                if (intval($this->module->overdriveLimit['post']) > 0) {
                    if (count($post) >= intval($this->module->overdriveLimit['post'])) {
                        $this->security->setBlock('overdrive');
                        Yii::debug('Overdrive attack detected in $_POST', __CLASS__);
                    }
                }
            }

            if (isset($this->module->overdriveLimit['get'])) {
                if (intval($this->module->overdriveLimit['get']) > 0) {
                    if (count($get) >= intval($this->module->overdriveLimit['get'])) {
                        $this->security->setBlock('overdrive');
                        Yii::debug('Overdrive attack detected in $_GET', __CLASS__);
                    }
                }
            }

        }

        if ($this->module->useFilters) {

            if (!$this->module->isBackend()) {
                $errorHandler = Yii::$app->getErrorHandler();
                $errorHandler->errorAction = $this->module->routePrefix . '/guard/default/error';
            }

            if ($this->module->filters['xss'] && isset($this->module->patterns['xss'])) {
                if ($this->security->checkAttack($this->module->patterns, 'xss', $post)) {
                    $this->security->setBlock('xss');
                    Yii::debug('XSS-attack detected in $_POST', __CLASS__);
                }
                if ($this->security->checkAttack($this->module->patterns, 'xss', $get)) {
                    $this->security->setBlock('xss');
                    Yii::debug('XSS-attack detected in $_GET', __CLASS__);
                }
            }

            if ($this->module->filters['lfi'] && isset($this->module->patterns['lfi'])) {
                if ($this->security->checkAttack($this->module->patterns, 'lfi', $post)) {
                    $this->security->setBlock('lfi');
                    Yii::debug('LFI/RFI/RCE attack detected in $_POST', __CLASS__);
                }
                if ($this->security->checkAttack($this->module->patterns, 'lfi', $get)) {
                    $this->security->setBlock('lfi');
                    Yii::debug('LFI/RFI/RCE attack detected in $_GET', __CLASS__);
                }
            }

            if ($this->module->filters['php'] && isset($this->module->patterns['php'])) {
                if ($this->security->checkAttack($this->module->patterns, 'php', $post)) {
                    $this->security->setBlock('php');
                    Yii::debug('PHP-injection detected in $_POST', __CLASS__);
                }
                if ($this->security->checkAttack($this->module->patterns, 'php', $get)) {
                    $this->security->setBlock('php');
                    Yii::debug('PHP-injection detected in $_GET', __CLASS__);
                }
            }

            if ($this->module->filters['sql'] && isset($this->module->patterns['sql'])) {
                if ($this->security->checkAttack($this->module->patterns, 'sql', $post)) {
                    $this->security->setBlock('sql');
                    Yii::debug('SQL-injection detected in $_POST', __CLASS__);
                }

                if ($this->security->checkAttack($this->module->patterns, 'sql', $get)) {
                    $this->security->setBlock('sql');
                    Yii::debug('SQL-injection detected in $_GET', __CLASS__);
                }
            }

        }

    }
}