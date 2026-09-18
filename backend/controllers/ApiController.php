<?php

namespace app\controllers;

use app\components\JwtAuth;
use yii\filters\AccessControl;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\Response;

abstract class ApiController extends Controller
{
    protected array $protectedActions = [];

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => [\Yii::$app->params['frontendOrigin']],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
            ],
        ];
        if ($this->protectedActions) {
            $behaviors['authenticator'] = [
                'class' => JwtAuth::class,
                'only' => $this->protectedActions,
            ];
            $behaviors['access'] = [
                'class' => AccessControl::class,
                'only' => $this->protectedActions,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => static fn () => \Yii::$app->user->identity?->role === 'user',
                ]],
            ];
        }
        return $behaviors;
    }

    protected function ok(mixed $data, int $status = 200): array
    {
        \Yii::$app->response->statusCode = $status;
        return ['success' => true, 'data' => $data];
    }

    protected function fail(string $message, ?string $field = null, int $status = 422): array
    {
        \Yii::$app->response->statusCode = $status;
        return ['success' => false, 'errors' => [['field' => $field, 'message' => $message]]];
    }

    protected function validationErrors(\yii\base\Model $model): array
    {
        \Yii::$app->response->statusCode = 422;
        $errors = [];
        foreach ($model->getErrors() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = ['field' => $field, 'message' => $message];
            }
        }
        return ['success' => false, 'errors' => $errors];
    }
}

