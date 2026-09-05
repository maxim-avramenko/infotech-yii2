<?php

use yii\db\Migration;

class m260905_000004_create_book_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'year' => $this->smallInteger()->notNull(),
            'description' => $this->text()->null(),
            'isbn' => $this->string(32)->notNull(),
            'image' => $this->string(45)->null(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->null(),
            'deleted_at' => $this->dateTime()->null(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-book-created_by', '{{%book}}', 'created_by');
        $this->createIndex('idx-book-deleted_at', '{{%book}}', 'deleted_at');
        $this->addForeignKey(
            'fk-book-created_by',
            '{{%book}}',
            'created_by',
            '{{%user}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );

        $this->createTable('{{%book_book_author}}', [
            'book_id' => $this->integer()->notNull(),
            'book_author_id' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->addPrimaryKey('pk-book_book_author', '{{%book_book_author}}', ['book_id', 'book_author_id']);
        $this->createIndex('idx-book_book_author-book_author_id', '{{%book_book_author}}', 'book_author_id');
        $this->addForeignKey(
            'fk-book_book_author-book_id',
            '{{%book_book_author}}',
            'book_id',
            '{{%book}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk-book_book_author-book_author_id',
            '{{%book_book_author}}',
            'book_author_id',
            '{{%book_author}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%book_book_author}}');
        $this->dropTable('{{%book}}');
    }
}
