<?php

namespace app\services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    /**
     * @return array{token: string, expires_at: string}
     */
    public static function issue(int $userId, string $username, string $role): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + \Yii::$app->params['jwtTtl'];
        $token = JWT::encode([
            'iss' => 'book-catalog-api',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (string) $userId,
            'username' => $username,
            'role' => $role,
        ], \Yii::$app->params['jwtSecret'], 'HS256');

        return ['token' => $token, 'expires_at' => gmdate('c', $expiresAt)];
    }

    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(\Yii::$app->params['jwtSecret'], 'HS256'));
    }
}
