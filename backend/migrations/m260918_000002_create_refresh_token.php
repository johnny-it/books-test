<?php

use yii\db\Migration;

class m260918_000002_create_refresh_token extends Migration
{
    public function safeUp(): bool
    {
        $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        $this->createTable('{{%refresh_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'family_id' => $this->char(32)->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'expires_at' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'revoked_at' => $this->dateTime(),
            'replaced_by_id' => $this->integer(),
        ], $options);

        $this->createIndex('uq-refresh-token-token-hash', '{{%refresh_token}}', 'token_hash', true);
        $this->createIndex('idx-refresh-token-user-id', '{{%refresh_token}}', 'user_id');
        $this->createIndex('idx-refresh-token-family-id', '{{%refresh_token}}', 'family_id');
        $this->createIndex('idx-refresh-token-expires-at', '{{%refresh_token}}', 'expires_at');

        $this->addForeignKey(
            'fk-refresh-token-user',
            '{{%refresh_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-refresh-token-replacement',
            '{{%refresh_token}}',
            'replaced_by_id',
            '{{%refresh_token}}',
            'id',
            'SET NULL'
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTable('{{%refresh_token}}');
        return true;
    }
}
