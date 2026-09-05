<?php

use yii\db\Migration;

class m260905_000003_add_deleted_at_to_book_author extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%book_author}}', 'deleted_at', $this->dateTime()->null());
        $this->createIndex('idx-book_author-deleted_at', '{{%book_author}}', 'deleted_at');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-book_author-deleted_at', '{{%book_author}}');
        $this->dropColumn('{{%book_author}}', 'deleted_at');
    }
}
