<?php

namespace app\models;

use yii\base\Model;

class LoginForm extends Model
{
    public string $username = '';
    public string $password = '';
    private ?User $user = null;

    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword(string $attribute): void
    {
        if (!$this->hasErrors()) {
            $this->user = User::findOne(['username' => $this->username]);
            if (!$this->user || !$this->user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неверный логин или пароль.');
            }
        }
    }

    public function getUser(): ?User { return $this->user; }
}

