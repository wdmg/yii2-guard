<?php

namespace wdmg\guard\controllers;

use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\web\Controller;
use yii\data\ActiveDataProvider;

/**
 * DefaultController implements actions for Blog model.
 */
class DefaultController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        // Set a forbidden error layout
        if (isset(Yii::$app->params["guard.forbiddenLayout"]))
            $this->layout = Yii::$app->params["guard.forbiddenLayout"];
        elseif (isset($this->module->forbiddenLayout))
            $this->layout = $this->module->forbiddenLayout;
        else
            $this->layout = null;

        return parent::beforeAction($action);
    }

    /**
     * Error/exeption action
     *
     * @return string
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            Yii::$app->view->clear();
            return $this->render('error', [
                'name' => ($exception instanceof Exception || $exception instanceof ErrorException) ? $exception->getName() : 'Exception',
                'message' => $exception->getMessage(),
                'code' => ($exception->getCode()) ? $exception->getCode() : (($exception->statusCode) ? $exception->statusCode : null),
                'status' => Yii::$app->errorHandler->getExceptionName($exception)
            ]);
        }
    }
}
