<?php

namespace app\services;

use app\models\RefreshToken;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;
use yii\web\UnauthorizedHttpException;

final class RefreshTokenService
{
    /**
     * @return array{token: string, expiresAt: int}
     */
    public function create(User $user): array
    {
        $rawToken = $this->generateToken();
        $expiresAt = time() + (int) Yii::$app->params['refreshTokenTtl'];

        $refreshToken = new RefreshToken([
            'user_id' => $user->getId(),
            'family_id' => bin2hex(random_bytes(16)),
            'token_hash' => $this->hash($rawToken),
            'expires_at' => gmdate('Y-m-d H:i:s', $expiresAt),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        if (!$refreshToken->save()) {
            throw new RuntimeException('Failed to persist refresh token.');
        }

        return ['token' => $rawToken, 'expiresAt' => $expiresAt];
    }

    /**
     * @return array{token: string, expiresAt: int, user: User}
     * @throws UnauthorizedHttpException
     */
    public function rotate(string $rawToken): array
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $refreshToken = $this->findForUpdate($this->hash($rawToken));

            if ($refreshToken === null || $refreshToken->isExpired(time())) {
                throw new UnauthorizedHttpException('Invalid refresh token.');
            }

            if ($refreshToken->isRevoked()) {
                if ($refreshToken->replaced_by_id !== null) {
                    RefreshToken::updateAll(
                        ['revoked_at' => gmdate('Y-m-d H:i:s')],
                        ['and', ['family_id' => $refreshToken->family_id], ['revoked_at' => null]]
                    );
                    $transaction->commit();
                }

                throw new UnauthorizedHttpException('Invalid refresh token.');
            }

            $user = User::findOne((int) $refreshToken->user_id);
            if ($user === null) {
                throw new UnauthorizedHttpException('Invalid refresh token.');
            }

            $replacementRawToken = $this->generateToken();
            $replacement = new RefreshToken([
                'user_id' => $refreshToken->user_id,
                'family_id' => $refreshToken->family_id,
                'token_hash' => $this->hash($replacementRawToken),
                'expires_at' => $refreshToken->expires_at,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            if (!$replacement->save()) {
                throw new RuntimeException('Failed to persist replacement refresh token.');
            }

            $refreshToken->revoked_at = gmdate('Y-m-d H:i:s');
            $refreshToken->replaced_by_id = $replacement->id;
            if (!$refreshToken->save(false, ['revoked_at', 'replaced_by_id'])) {
                throw new RuntimeException('Failed to revoke rotated refresh token.');
            }

            $transaction->commit();

            return [
                'token' => $replacementRawToken,
                'expiresAt' => strtotime($replacement->expires_at . ' UTC'),
                'user' => $user,
            ];
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            throw $exception;
        }
    }

    public function revoke(string $rawToken): void
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $refreshToken = $this->findForUpdate($this->hash($rawToken));

            if ($refreshToken !== null && !$refreshToken->isRevoked()) {
                $refreshToken->revoked_at = gmdate('Y-m-d H:i:s');
                if (!$refreshToken->save(false, ['revoked_at'])) {
                    throw new RuntimeException('Failed to revoke refresh token.');
                }
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            throw $exception;
        }
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function findForUpdate(string $tokenHash): ?RefreshToken
    {
        return RefreshToken::findBySql(
            'SELECT * FROM {{%refresh_token}} WHERE [[token_hash]] = :tokenHash FOR UPDATE',
            [':tokenHash' => $tokenHash]
        )->one();
    }
}
