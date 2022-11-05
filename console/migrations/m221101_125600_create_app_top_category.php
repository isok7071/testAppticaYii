<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m221101_125600_create_app_top_category
 */
class m221101_125600_create_app_top_category extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->createTable('app_top_category', [
            'id' => Schema::TYPE_PK,
            'category' => Schema::TYPE_INTEGER . ' NOT NULL',
            'position' => Schema::TYPE_INTEGER,
            'date'=>Schema::TYPE_DATE . ' NOT NULL'
        ]);
        // creates index for column `post_id`
        $this->createIndex(
            'date',
            'app_top_category',
            'date'
        );
    }

    public function down()
    {
        $this->dropIndex(
            'date',
            'app_top_category'
        );
        $this->dropTable('app_top_category');
    }
}
