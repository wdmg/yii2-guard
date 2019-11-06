<?php

namespace wdmg\guard\filters;

use Yii;
use yii\web\Request;
use yii\web\Response;
use yii\filters\RateLimiter;


class RateLimit extends RateLimiter
{
    /**
     * @var bool whether to include rate limit headers in the response
     */
    public $enableRateLimitHeaders = true;

    /**
     * @var string the message to be displayed when rate limit exceeds
     */
    public $errorMessage = 'Rate limit exceeded.';

    /**
     * Base Guard Module
     */
    public $module;

    /**
     * Base Security Model
     */
    public $security;

    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;

    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;

    /**
     * @var Rate limit.
     */
    public $rateLimit = 60;

    /**
     * @var Session key for storage rate limit of requests.
     */
    private $sessionKey = 'rateLimit';

    /**
     * {@inheritdoc}
     */
    public function getRateLimit($request, $action) {
        return [$this->rateLimit, 60];
    }

    /**
     * {@inheritdoc}
     */
    public function loadAllowance($request, $action) {
        $session = Yii::$app->session->get($this->sessionKey);
        return [$session['allowance'], $session['updated_at']];
    }

    /**
     * {@inheritdoc}
     */
    public function saveAllowance($request, $action, $allowance, $timestamp) {
        Yii::$app->session->set($this->sessionKey, [
            'allowance' => intval($allowance),
            'updated_at' => intval($timestamp),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {

        if (!$this->hasIgnoring()) {
            $session = Yii::$app->session->get($this->sessionKey);
            Yii::debug('Check rate limit', __METHOD__);
            $this->checkRateLimit($session, $this->request, $this->response, $action);
        } else {
            Yii::debug('Ignoring check rate limit by rules', __METHOD__);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkRateLimit($session, $request, $response, $action)
    {

        list($limit, $window) = $this->getRateLimit($request, $action);
        list($allowance, $timestamp) = $this->loadAllowance($request, $action);

        $current = time();

        $allowance += (int) (($current - $timestamp) * $limit / $window);
        if ($allowance > $limit) {
            $allowance = $limit;
        }

        if ($allowance < 1) {
            $this->saveAllowance($request, $action, 0, $current);
            $this->addRateLimitHeaders($response, $limit, 0, $window);

            if (isset($this->module->rateLimitErrorMessage))
                $this->errorMessage = $this->module->rateLimitErrorMessage;

            $handler = Yii::$app->getErrorHandler();
            if ($action->getUniqueId($action->id) !== $handler->errorAction) // Self-circuit protection
                $this->security->setBlock('ratelimit', $this->errorMessage);

        }

        $this->saveAllowance($request, $action, $allowance - 1, $current);
        $this->addRateLimitHeaders($response, $limit, $allowance - 1, (int) (($limit - $allowance + 1) * $window / $limit));
    }

    /**
     * Checks whether it is necessary to ignore the control of the rate limit of requests in this case
     * @return boolean
     */
    private function hasIgnoring() {

        $ignoring = false;

        if (isset($this->module->rateLimitIgnoringRequests['post'])) {
            if (Yii::$app->request->isPost && $this->module->rateLimitIgnoringRequests['post'])
                $ignoring = true;

        }

        if (isset($this->module->rateLimitIgnoringRequests['get'])) {
            if (Yii::$app->request->isGet && $this->module->rateLimitIgnoringRequests['get'])
                $ignoring = true;

        }

        if (isset($this->module->rateLimitIgnoringRequests['ajax'])) {
            if (Yii::$app->request->isAjax && $this->module->rateLimitIgnoringRequests['ajax'])
                $ignoring = true;

        }

        if (is_array($this->module->rateLimitIgnoringRoutes)) {

            $ignoringByRoute = false;
            $exceptionByRoute = false;

            $url = Yii::$app->request->getUrl();
            foreach ($this->module->rateLimitIgnoringRoutes as $pattern) {
                if (preg_match("/^" . preg_quote($pattern, "/") . "/", $url)) {
                    $ignoringByRoute = true;
                    break;
                }
            }

            if ($ignoringByRoute && is_array($this->module->rateLimitExceptionRoutes)) {
                foreach ($this->module->rateLimitExceptionRoutes as $pattern) {
                    if (preg_match("/^" . preg_quote($pattern, "/") . "/", $url)) {
                        $exceptionByRoute = true;
                        break;
                    }
                }
            }

            $ignoring = (!$exceptionByRoute && $ignoringByRoute);
        }

        if (is_array($this->module->rateLimitIgnoringIP)) {
            if (in_array(Yii::$app->request->userIP, $this->module->rateLimitIgnoringIP))
                $ignoring = true;

        }

        return $ignoring;
    }

}