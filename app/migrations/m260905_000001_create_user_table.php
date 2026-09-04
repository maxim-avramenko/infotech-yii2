<?php

use yii\db\Migration;

class m260905_000001_create_user_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(255)->notNull(),
            'phone' => $this->string(15)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'role' => $this->string(32)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'email_verified_at' => $this->dateTime()->null(),
            'phone_verified_at' => $this->dateTime()->null(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex('uniq-user-email', '{{%user}}', 'email', true);
        $this->createIndex('uniq-user-phone', '{{%user}}', 'phone', true);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user}}');
    }
}
