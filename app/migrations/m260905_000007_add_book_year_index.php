<?php

use yii\db\Migration;

class m260905_000007_add_book_year_index extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex('idx-book-year-deleted_at', '{{%book}}', ['year', 'deleted_at']);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-book-year-deleted_at', '{{%book}}');
    }
}
