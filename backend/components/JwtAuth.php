<?php

namespace app\components;

use app\models\User;
use app\services\JwtService;
use yii\filters\auth\AuthMethod;

class JwtAuth extends AuthMethod
{
    public function authenticate($user, $request, $response)
    {
        $header = $request->headers->get('Authorization');
        if (!$header || !preg_match('/^Bearer\s+(.*?)$/', $header, $matches)) {
            return null;
        }

        try {
            $payload = JwtService::decode($matches[1]);
            $identity = User::findIdentity((int) $payload->sub);
            if (!$identity || $identity->role !== 'user') {
                return null;
            }
            $user->login($identity);
            return $identity;
        } catch (\Throwable) {
            return null;
        }
    }
}

