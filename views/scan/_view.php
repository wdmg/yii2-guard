<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\Scanning */

\yii\web\YiiAsset::register($this);

?>
<div class="scan-view-report">
<?php
echo "<ul>";
foreach ($files as $file) {
    echo "<li>" . Yii::t('app/modules/guard', 'File `{filename}` has been modified at {modified}', [
            'filename' => $file['filename'],
            'modified' => $file['modified'],
        ]) . "</li>";
}
echo "</ul>";
?>
</div>