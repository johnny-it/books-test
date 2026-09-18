<?php

namespace app\services;

use Yii;
use yii\web\Cookie;

final class RefreshCookieService
{
    private const PATH = '/api/v1/auth';

    public function read(): ?string
    {
        $value = Yii::$app->request->cookies->getValue(Yii::$app->params['refreshCookieName']);

        return is_string($value) ? $value : null;
    }

    public function write(string $token, int $expiresAt): void
    {
        Yii::$app->response->cookies->add(new Cookie($this->cookieConfig([
            'value' => $token,
            'expire' => $expiresAt,
        ])));
    }

    public function clear(): void
    {
        Yii::$app->response->cookies->remove(new Cookie($this->cookieConfig()));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function cookieConfig(array $overrides = []): array
    {
        $config = [
            'name' => Yii::$app->params['refreshCookieName'],
            'httpOnly' => true,
            'secure' => (bool) Yii::$app->params['refreshCookieSecure'],
            'sameSite' => Cookie::SAME_SITE_LAX,
            'path' => self::PATH,
        ];

        $domain = (string) Yii::$app->params['refreshCookieDomain'];
        if ($domain !== '') {
            $config['domain'] = $domain;
        }

        return array_merge($config, $overrides);
    }
}
