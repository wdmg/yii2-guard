<?php

use wdmg\widgets\DatePicker;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use wdmg\widgets\SelectInput;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\Security */

$this->title = Yii::t('app/modules/guard', 'Banned List');
$this->params['breadcrumbs'][] = Yii::t('app/modules/guard', 'Security');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="page-header">
    <h1>
        <?= Html::encode($this->title) ?> <small class="text-muted pull-right">[v.<?= $this->context->module->version ?>]</small>
    </h1>
</div>
<div class="guard-banned-index">
    <?php Pjax::begin([
        'id' => "guardBannedAjax",
        'timeout' => 5000
    ]); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '{summary}<br\/>{items}<br\/>{summary}<br\/><div class="text-center">{pager}</div>',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'client_ip',
                'label' => Yii::t('app/modules/guard','Сlient IP/Net'),
                'format' => 'html',
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {

                    $output = '';
                    if ($data->client_ip)
                        $output .= long2ip(intval($data->client_ip));

                    if ($data->client_net)
                        $output .= (!empty($output) ? ', ' : '') . $data->client_net;

                    return $output;
                }
            ],

            /*'client_net',
            'user_agent',*/

            [
                'attribute' => 'reason',
                'format' => 'html',
                'filter' => SelectInput::widget([
                    'model' => $searchModel,
                    'attribute' => 'reason',
                    'items' => $searchModel->getReasonsList(true),
                    'options' => [
                        'id' => 'banned-reason',
                        'class' => 'form-control'
                    ]
                ]),
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {
                    $reasons = $data->getReasonsList(false);
                    if (isset($reasons[$data->reason]))
                        return $reasons[$data->reason];
                    else
                        return $data->reason;
                }
            ],

            [
                'attribute' => 'status',
                'format' => 'html',
                'filter' => SelectInput::widget([
                    'model' => $searchModel,
                    'attribute' => 'status',
                    'items' => $searchModel->getStatusesList(true),
                    'options' => [
                        'id' => 'banned-status',
                        'class' => 'form-control'
                    ]
                ]),
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {
                    if ($data->status == $data::GUARD_STATUS_IS_RELEASED)
                        return '<span class="label label-success">'.Yii::t('app/modules/guard','Released').'</span>';
                    elseif ($data->status == $data::GUARD_STATUS_IS_UNBANNED)
                        return '<span class="label label-info">'.Yii::t('app/modules/guard','Unbanned').'</span>';
                    elseif ($data->status == $data::GUARD_STATUS_IS_BANNED)
                        return '<span class="label label-danger">'.Yii::t('app/modules/guard','Banned').'</span>';
                    else
                        return $data->status;
                }
            ],

            [
                'attribute' => 'created_at',
                'label' => Yii::t('app/modules/guard','Created'),
                'format' => 'html',
                'filter' => DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'created_at',
                    'options' => [
                        'id' => 'banned-created',
                        'class' => 'form-control',
                        'value' => date('d.m.Y H:i:s')
                    ],
                    'pluginOptions' => [
                        'className' => '.datepicker',
                        'input' => '.form-control',
                        'format' => 'DD.MM.YYYY HH:mm:ss',
                        'toggle' => '.input-group-btn > button',
                    ]
                ]),
                'value' => function($data) {

                    $output = "";
                    if ($user = $data->createdBy) {
                        $output = Html::a($user->username, ['../admin/users/view/?id='.$user->id], [
                            'target' => '_blank',
                            'data-pjax' => 0
                        ]);
                    } else if ($data->created_by) {
                        $output = $data->created_by;
                    }

                    if (!empty($output))
                        $output .= ", ";

                    $output .= Yii::$app->formatter->format($data->created_at, 'datetime');
                    return $output;
                }
            ],
            [
                'attribute' => 'updated_at',
                'label' => Yii::t('app/modules/guard','Updated'),
                'format' => 'html',
                'filter' => DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'updated_at',
                    'options' => [
                        'id' => 'banned-updated',
                        'class' => 'form-control',
                        'value' => date('d.m.Y H:i:s')
                    ],
                    'pluginOptions' => [
                        'className' => '.datepicker',
                        'input' => '.form-control',
                        'format' => 'DD.MM.YYYY HH:mm:ss',
                        'toggle' => '.input-group-btn > button',
                    ]
                ]),
                'value' => function($data) {

                    $output = "";
                    if ($user = $data->updatedBy) {
                        $output = Html::a($user->username, ['../admin/users/view/?id='.$user->id], [
                            'target' => '_blank',
                            'data-pjax' => 0
                        ]);
                    } else if ($data->updated_by) {
                        $output = $data->updated_by;
                    }

                    if (!empty($output))
                        $output .= ", ";

                    $output .= Yii::$app->formatter->format($data->updated_at, 'datetime');
                    return $output;
                }
            ],
            [
                'attribute' => 'release_at',
                'label' => Yii::t('app/modules/guard','Release'),
                'format' => 'html',
                'filter' => DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'release_at',
                    'options' => [
                        'id' => 'banned-release',
                        'class' => 'form-control',
                        'value' => date('d.m.Y H:i:s')
                    ],
                    'pluginOptions' => [
                        'className' => '.datepicker',
                        'input' => '.form-control',
                        'format' => 'DD.MM.YYYY HH:mm:ss',
                        'toggle' => '.input-group-btn > button',
                    ]
                ]),
                'value' => function($data) {
                    return Yii::$app->formatter->format($data->release_at, 'datetime');
                }
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'header' => Yii::t('app/modules/guard','Actions'),
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
            ]
        ],
        'pager' => [
            'options' => [
                'class' => 'pagination',
            ],
            'maxButtonCount' => 5,
            'activePageCssClass' => 'active',
            'prevPageCssClass' => '',
            'nextPageCssClass' => '',
            'firstPageCssClass' => 'previous',
            'lastPageCssClass' => 'next',
            'firstPageLabel' => Yii::t('app/modules/guard', 'First page'),
            'lastPageLabel'  => Yii::t('app/modules/guard', 'Last page'),
            'prevPageLabel'  => Yii::t('app/modules/guard', '&larr; Prev page'),
            'nextPageLabel'  => Yii::t('app/modules/guard', 'Next page &rarr;')
        ],
    ]); ?>
    <hr/>
    <div>
        <?= Html::a(Yii::t('app/modules/guard', 'Add/update'), ['banned/create'], [
            'class' => 'btn btn-add btn-success pull-right',
            'data-toggle' => 'modal',
            'data-target' => '#addNewBanned',
            'data-pjax' => '1'
        ]) ?>
    </div>
    <?php Pjax::end(); ?>
</div>

<?php $this->registerJs(<<< JS
$('body').delegate('[data-toggle="modal"][data-target="#addNewBanned"]', 'click', function(event) {
    event.preventDefault();
    $.get(
        $(this).attr('href'),
        function (data) {
            $('#addNewBanned .modal-body').html($(data).remove('.modal-footer'));
            if ($(data).find('.modal-footer').length > 0) {
                $('#addNewBanned').find('.modal-footer').remove();
                $('#addNewBanned .modal-content').append($(data).find('.modal-footer'));
            }
            $('#addNewBanned').modal();
        }  
    );
});
JS
); ?>
<?php Modal::begin([
    'id' => 'addNewBanned',
    'header' => '<h4 class="modal-title">'.Yii::t('app/modules/guard', 'Banned Client').'</h4>',
    'footer' => '<a href="#" class="btn btn-primary" data-dismiss="modal">'.Yii::t('app/modules/guard', 'Close').'</a>',
    'clientOptions' => [
        'show' => false
    ]
]); ?>
<?php Modal::end(); ?>

<?php echo $this->render('../_debug'); ?>