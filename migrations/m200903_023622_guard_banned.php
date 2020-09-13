<?php

use yii\db\Migration;

/**
 * Class m200903_023622_guard_banned
 */
class m200903_023622_guard_banned extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%guard_banned}}', [
            'id' => $this->primaryKey(),
            'client_ip' => $this->bigInteger(15)->unsigned()->null(),
            'client_net' => $this->string(255)->null(),
            'range_start' => $this->bigInteger(15)->unsigned()->null(),
            'range_end' => $this->bigInteger(15)->unsigned()->null(),
            'user_agent' => $this->string(255)->null(),
            'status' => $this->tinyInteger(1),
            'reason' => $this->string(255),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_by' => $this->integer(11),
            'updated_at' => $this->datetime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_by' => $this->integer(11),
            'release_at' => $this->datetime(),
        ], $tableOptions);

        $this->createIndex(
            'idx_guard_banned',
            '{{%guard_banned}}',
            [
                'client_ip',
                'client_net',
                'user_agent',
                'status',
            ]
        );

        if (class_exists('\wdmg\users\models\Users')) {
            $this->createIndex('{{%idx-banned-author}}','{{%guard_banned}}', ['created_by', 'updated_by'], false);
            $userTable = \wdmg\users\models\Users::tableName();
            if (!(Yii::$app->db->getTableSchema($userTable, true) === null)) {
                $this->addForeignKey(
                    'fk_banned_created2users',
                    '{{%guard_banned}}',
                    'created_by',
                    $userTable,
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
                $this->addForeignKey(
                    'fk_banned_updated2users',
                    '{{%guard_banned}}',
                    'updated_by',
                    $userTable,
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_guard_banned', '{{%guard_banned}}');

        if (class_exists('\wdmg\users\models\Users')) {
            $userTable = \wdmg\users\models\Users::tableName();
            if (!(Yii::$app->db->getTableSchema($userTable, true) === null)) {
                $this->dropIndex('{{%idx-banned-author}}', '{{%guard_banned}}');
                $this->dropForeignKey(
                    'fk_banned_created2users',
                    '{{%guard_banned}}'
                );
                $this->dropForeignKey(
                    'fk_banned_updated2users',
                    '{{%guard_banned}}'
                );
            }
        }

        $this->truncateTable('{{%guard_banned}}');
        $this->dropTable('{{%guard_banned}}');
    }

}
