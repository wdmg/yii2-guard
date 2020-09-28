<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>
<h1><?= Yii::t('app/modules/guard', 'Total scan result from {datetime}', [
        'datetime' => date("F d Y H:i:s", $details['timestamp'])
    ]); ?></h1>

<p><?= Yii::t('app/modules/guard', 'Scanning {dirs} dirs and {files} files completed in {time} sec.', [
        'dirs' => $details['dirs'],
        'files' => $details['files'],
        'time' => round($details['time'], 2)
    ]); ?></p>
<?php
    if (count($files) > 0) :
?>
<p><?= Yii::t('app/modules/guard', 'The following files have been added/changed:'); ?></p>
<?php
        echo "<ul>";
            foreach ($files as $file) {
                echo "<li>" . Yii::t('app/modules/guard', 'File `{filename}` has been modified at {modified}', [
                    'filename' => $file['filename'],
                    'modified' => $file['modified'],
                ]) . "</li>";
            }
        echo "</ul>";
    else :
?>
<p><?= Yii::t('app/modules/guard', 'There are no changes to tracked files.'); ?></p>
<?php
    endif;
?>
