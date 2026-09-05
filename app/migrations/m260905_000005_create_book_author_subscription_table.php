<?php

use yii\db\Migration;

class m260905_000005_create_book_author_subscription_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book_author_subscription}}', [
            'id' => $this->primaryKey(),
            'book_author_id' => $this->integer()->notNull(),
            'phone' => $this->string(15)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex(
            'uq-book_author_subscription-author-phone',
            '{{%book_author_subscription}}',
            ['book_author_id', 'phone'],
            true,
        );
        $this->createIndex('idx-book_author_subscription-phone', '{{%book_author_subscription}}', 'phone');
        $this->addForeignKey(
            'fk-book_author_subscription-book_author_id',
            '{{%book_author_subscription}}',
            'book_author_id',
            '{{%book_author}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%book_author_subscription}}');
    }
}
