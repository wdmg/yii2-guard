<?php

namespace wdmg\guard\commands;

use wdmg\guard\models\Scanning;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

use yii\helpers\Console;
use yii\helpers\ArrayHelper;

class InitController extends Controller
{
    /**
     * @inheritdoc
     */
    public $choice = null;

    /**
     * @inheritdoc
     */
    public $defaultAction = 'index';

    public function options($actionID)
    {
        return ['choice', 'color', 'interactive', 'help'];
    }

    public function actionIndex($params = null)
    {
        $version = Yii::$app->controller->module->version;
        $welcome =
            '╔════════════════════════════════════════════════╗'. "\n" .
            '║                                                ║'. "\n" .
            '║              GUARD MODULE, v.'.$version.'             ║'. "\n" .
            '║          by Alexsander Vyshnyvetskyy           ║'. "\n" .
            '║       (c) 2019-2023 W.D.M.Group, Ukraine       ║'. "\n" .
            '║                                                ║'. "\n" .
            '╚════════════════════════════════════════════════╝';
        echo $name = $this->ansiFormat($welcome . "\n\n", Console::FG_GREEN);
        echo "Select the operation you want to perform:\n";
        echo "  1) Apply all module migrations\n";
        echo "  2) Revert all module migrations\n";
        echo "  3) Scan filesystem for modifications\n";
        echo "  4) Clear all filesystem scan reports\n";
        echo "  5) Delete all filesystem scan reports\n\n";
        echo "Your choice: ";

        if(!is_null($this->choice))
            $selected = $this->choice;
        else
            $selected = trim(fgets(STDIN));

        if ($selected == "1") {

            Yii:: $app->runAction('migrate/up', ['migrationPath' => '@vendor/wdmg/yii2-guard/migrations', 'interactive' => true]);

        } else if($selected == "2") {

            Yii::$app->runAction('migrate/down', ['migrationPath' => '@vendor/wdmg/yii2-guard/migrations', 'interactive' => true]);

        } else if ($selected == "3") {

            echo $this->ansiFormat("\nFilesystem scanning started...\n", Console::FG_YELLOW);

            $scanner = new Scanning();
            if ($runtime = $scanner->scan()) {

                list($dirs, $files, $time, $modified) = [
                    $runtime['summary']['dirs'],
                    $runtime['summary']['files'],
                    $runtime['summary']['time'],
                    $runtime['summary']['modified'],
                ];
                echo "Scanning $dirs dirs and $files files completed in $time sec.\n";

                if ($modified)
                    echo $this->ansiFormat("Changes detected! $modified files have been modified since the last scan.\n", Console::FG_RED);

                echo $this->ansiFormat("Filesystem scanning complete!\n", Console::FG_GREEN);
            } else {
                echo $this->ansiFormat("Error filesystem scanning.\n", Console::FG_RED);
            }

        } else if ($selected == "4") {

            $scanner = new Scanning();
            if ($scanner->clearOldReports(true))
                echo $this->ansiFormat("Filesystem scan reports cleared succesfull!\n", Console::FG_GREEN);
            else
                echo $this->ansiFormat("Error clearing filesystem scanning reports.\n", Console::FG_RED);

        } else if ($selected == "5") {

            $scanner = new Scanning();
            if ($scanner->deleteAll())
                echo $this->ansiFormat("Filesystem scan reports deleted succesfull!\n", Console::FG_GREEN);
            else
                echo $this->ansiFormat("Error deleting filesystem scanning reports.\n", Console::FG_RED);

        } else {
            echo $this->ansiFormat("Error! Your selection has not been recognized.\n\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        echo "\n";
        return ExitCode::OK;
    }
}
