<?php

namespace wdmg\guard\controllers;

use Yii;
use wdmg\guard\models\Scanning;
use wdmg\guard\models\ScanningSearch;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ScanController implements the CRUD actions for Scanning report.
 */
class ScanController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'roles' => ['admin'],
                        'allow' => true
                    ],
                ],
            ],
        ];

        // If auth manager not configured use default access control
        if (!Yii::$app->authManager) {
            $behaviors['access'] = [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'roles' => ['@'],
                        'allow' => true
                    ],
                ]
            ];
        }

        return $behaviors;
    }

    /**
     * Lists all Scanning reports.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new Scanning();
        $searchModel = new ScanningSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Scanning report.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    /*public function actionScan($id)
    {
        $model = new Scanning();
        $model->scan();

        return $this->render('index', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }*/

    public function actionScan($action = null)
    {
        if (Yii::$app->request->isAjax) {
            if ($action == 'progress') {
                $report = Yii::$app->getCache()->get('scan-report');
                return $this->asJson([
                    'progress' => ((isset($report['progress'])) ? $report['progress'] : 0),
                    'status' => ((isset($report['status'])) ? $report['status'] : false),
                    'log' => ((isset($report['runtime']['log'])) ? $report['runtime']['log'] : null)
                ]);
            } else if ($action == 'run') {
                if ($module = Yii::$app->getModule('admin/guard'))
                    return $this->asJson($module->runConsole('admin/guard --choice 3'));
                elseif ($module = Yii::$app->getModule('guard'))
                    return $this->asJson($module->runConsole('guard --choice 3'));
            }
            return $this->renderAjax('_scan');
        }
        return $this->redirect(['index']);
    }

    /**
     * Displays a single Scanning report.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (Yii::$app->request->isAjax) {
            $model = $this->findModel($id);
            if ($data = $model->compareReports($model->data, $id)) {
                $report = $model->buildReport($data, false);
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $report
                ]);
                return $this->renderAjax('_view', [
                    'model' => $model,
                    'report' => $dataProvider,
                ]);
            }
        }
        return $this->redirect(['index']);
    }

    /**
     * Deletes an all Scanning reports.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete()
    {
        $model = new Scanning();
        $model->deleteAll();
        return $this->redirect(['index']);
    }

    /**
     * Clear an all data of Scanning reports.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionClear()
    {
        $model = new Scanning();
        $model->clearOldReports(true);
        return $this->redirect(['index']);
    }

    /**
     * Finds the Scanning report based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Scanning the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Scanning::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app/modules/guard', 'The requested page does not exist.'));
    }
}
