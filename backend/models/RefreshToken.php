<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class RefreshToken extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%refresh_token}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'family_id', 'token_hash', 'expires_at', 'created_at'], 'required'],
            [['user_id', 'replaced_by_id'], 'integer'],
            ['family_id', 'match', 'pattern' => '/^[a-f0-9]{32}$/i'],
            ['token_hash', 'match', 'pattern' => '/^[a-f0-9]{64}$/i'],
            [['expires_at', 'created_at', 'revoked_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function isExpired(int $now): bool
    {
        return strtotime($this->expires_at . ' UTC') <= $now;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
