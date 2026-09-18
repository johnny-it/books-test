<?php

namespace app\controllers;

use app\components\OriginFilter;
use app\models\LoginForm;
use app\models\User;
use app\services\JwtService;
use app\services\RefreshCookieService;
use app\services\RefreshTokenService;
use yii\web\UnauthorizedHttpException;

class AuthController extends ApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['originFilter'] = [
            'class' => OriginFilter::class,
            'only' => ['refresh', 'logout'],
        ];

        return $behaviors;
    }

    public function actionLogin(): array
    {
        $form = new LoginForm();
        $form->load(\Yii::$app->request->bodyParams, '');
        if (!$form->validate()) {
            \Yii::$app->response->statusCode = 401;
            return ['success' => false, 'errors' => [['field' => 'password', 'message' => 'Неверный логин или пароль.']]];
        }

        $user = $form->getUser();
        $refresh = (new RefreshTokenService())->create($user);
        (new RefreshCookieService())->write($refresh['token'], $refresh['expiresAt']);

        return $this->accessResponse($user);
    }

    public function actionRefresh(): array
    {
        $cookies = new RefreshCookieService();
        $rawToken = $cookies->read();
        if ($rawToken === null || $rawToken === '') {
            throw new UnauthorizedHttpException('Invalid refresh token.');
        }

        $refresh = (new RefreshTokenService())->rotate($rawToken);
        $cookies->write($refresh['token'], $refresh['expiresAt']);

        return $this->accessResponse($refresh['user']);
    }

    public function actionLogout(): array
    {
        $cookies = new RefreshCookieService();
        try {
            $rawToken = $cookies->read();
            if ($rawToken !== null && $rawToken !== '') {
                (new RefreshTokenService())->revoke($rawToken);
            }
        } finally {
            $cookies->clear();
        }

        return $this->ok(null);
    }

    private function accessResponse(User $user): array
    {
        $jwt = JwtService::issue((int) $user->id, $user->username, $user->role);

        return $this->ok([
            ...$jwt,
            'user' => ['id' => (int) $user->id, 'username' => $user->username, 'role' => $user->role],
        ]);
    }
}
