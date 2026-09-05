<?php

use yii\db\Migration;

class m260905_000002_create_book_author_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book_author}}', [
            'id' => $this->primaryKey(),
            'lastname' => $this->string(255)->notNull(),
            'firstname' => $this->string(255)->notNull(),
            'secondname' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-book_author-created_by', '{{%book_author}}', 'created_by');
        $this->addForeignKey(
            'fk-book_author-created_by',
            '{{%book_author}}',
            'created_by',
            '{{%user}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%book_author}}');
    }
}
