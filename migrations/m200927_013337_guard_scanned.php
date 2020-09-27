<?php

use yii\db\Migration;

/**
 * Class m200927_013337_guard_scanned
 */
class m200927_013337_guard_scanned extends Migration
{
    use wdmg\helpers\DbSchemaTrait;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%guard_scanned}}', [
            'id' => $this->primaryKey(),
            'logs' => $this->text(),
            'data' => $this->longText(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->datetime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->truncateTable('{{%guard_scanned}}');
        $this->dropTable('{{%guard_scanned}}');
    }

}
