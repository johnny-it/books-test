<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string { return '{{%user}}'; }
    public static function findIdentity($id): ?self { return self::findOne(['id' => $id]); }
    public static function findIdentityByAccessToken($token, $type = null): ?self { return null; }
    public function getId(): int { return (int) $this->id; }
    public function getAuthKey(): ?string { return null; }
    public function validateAuthKey($authKey): bool { return false; }
    public function validatePassword(string $password): bool { return \Yii::$app->security->validatePassword($password, $this->password_hash); }
}

