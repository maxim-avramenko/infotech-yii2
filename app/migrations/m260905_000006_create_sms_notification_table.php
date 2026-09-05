<?php

use yii\db\Migration;

class m260905_000006_create_sms_notification_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%sms_notification}}', [
            'id' => $this->primaryKey(),
            'book_id' => $this->integer()->null(),
            'phone' => $this->string(15)->notNull(),
            'text' => $this->string(512)->notNull(),
            'status' => $this->string(16)->notNull(),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'last_error' => $this->string(255)->null(),
            'next_attempt_at' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'sent_at' => $this->dateTime()->null(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('idx-sms_notification-status-next', '{{%sms_notification}}', ['status', 'next_attempt_at']);
        $this->createIndex('idx-sms_notification-book_id', '{{%sms_notification}}', 'book_id');
        $this->createIndex(
            'uq-sms_notification-book-phone',
            '{{%sms_notification}}',
            ['book_id', 'phone'],
            true,
        );
        $this->addForeignKey(
            'fk-sms_notification-book_id',
            '{{%sms_notification}}',
            'book_id',
            '{{%book}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%sms_notification}}');
    }
}
