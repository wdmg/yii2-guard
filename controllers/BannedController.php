<?php

namespace wdmg\guard\controllers;

use Yii;
use wdmg\guard\models\Security;
use wdmg\guard\models\BannedForm;
use wdmg\guard\models\SecuritySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * BannedController implements the CRUD actions for Security model.
 */
class BannedController extends Controller
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
     * Lists all Security models.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new BannedForm();
        $searchModel = new SecuritySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {

        $model = new BannedForm();
        $model->scenario = "add";
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if (!Yii::$app->request->isAjax) {
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                $result = $model->save();
                if (!empty($result['errors']) && $result['count'] == 0) {
                    Yii::$app->getSession()->setFlash(
                        'danger',
                        Yii::t('app/modules/guard', 'An error occurred while add the addresses: {errors}', [
                            'errors' => \yii\helpers\Html::ul((array)$result['errors'])
                        ])
                    );
                } else if (!empty($result['errors']) && $result['count'] > 0) {
                    Yii::$app->getSession()->setFlash(
                        'warning',
                        Yii::t('app/modules/guard', '{count} addresses were added successfully, but some errors occurred: {errors}', [
                            'errors' => \yii\helpers\Html::ul((array)$result['errors']),
                            'count' => $result['count']
                        ])
                    );
                } else {
                    Yii::$app->getSession()->setFlash(
                        'success',
                        Yii::t('app/modules/guard', '{count} addresses added successfully!', [
                            'count' => $result['count']
                        ])
                    );
                }
            }
            if (!empty($model->errors)) {
                Yii::$app->getSession()->setFlash(
                    'danger',
                    Yii::t('app/modules/guard', 'An error occurred while add the addresses: {errors}', [
                        'errors' => \yii\helpers\Html::ul((array)$model->errors)
                    ])
                );
            }
            return $this->redirect(['index']);
        }

        return $this->renderAjax('_form', [
            'model' => $model
        ]);
    }

    public function actionTest()
    {
        $model = new BannedForm();
        $model->scenario = "test";
        if (Yii::$app->request->isAjax && $post = Yii::$app->request->post()) {
            if (!isset($post['process']) && $model->load($post)) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            } else if (isset($post['process']) && $model->load($post)) {
                if ($model->validate() && $results = $model->test()) {
                    return $this->renderAjax('_results', [
                        'results' => $results,
                        'model' => $model
                    ]);
                }
            }
        }

        return $this->renderAjax('_test', [
            'model' => $model
        ]);
    }

    public function actionBulk() {
        if (Yii::$app->request->isAjax && $post = Yii::$app->request->post()) {

            $model = new BannedForm();
            if (isset($post['action']) && isset($post['selected'])) {

                $action = $post['action'];
                $selected = $post['selected'];
                switch ($action) {
                    case $model::GUARD_STATUS_IS_BANNED:
                        $model::updateAll(['status' => $model::GUARD_STATUS_IS_BANNED], ['id' => $selected]);
                        break;

                    case $model::GUARD_STATUS_IS_UNBANNED:
                        $model::updateAll(['status' => $model::GUARD_STATUS_IS_UNBANNED], ['id' => $selected]);
                        break;

                    case $model::GUARD_STATUS_IS_RELEASED:
                        $model::updateAll(['status' => $model::GUARD_STATUS_IS_RELEASED], ['id' => $selected]);
                        break;

                    case $model::GUARD_STATUS_IS_DELETED:
                        $model::deleteAll(['id' => $selected]);
                        break;

                }
            }
        }
        return $this->redirect(['index']);
    }

    /**
     * Displays a single Security model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Deletes an existing Security model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Security model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Security the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Security::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app/modules/guard', 'The requested page does not exist.'));
    }
}
