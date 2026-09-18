<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'cors' => [
                'class' => \yii\filters\Cors::class,
                'cors' => [
                    'Origin' => [\Yii::$app->params['frontendOrigin']],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                ],
            ],
        ];
    }

    public function actionHealth(): array
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => true, 'data' => ['status' => 'ok']];
    }

    public function actionOptions(): array
    {
        \Yii::$app->response->statusCode = 204;
        return [];
    }

    public function actionError(): array
    {
        $exception = \Yii::$app->errorHandler->exception;
        $status = $exception?->statusCode ?? 500;
        \Yii::$app->response->statusCode = $status;
        return [
            'success' => false,
            'errors' => [['field' => null, 'message' => $status === 500 ? 'Внутренняя ошибка сервера.' : $exception->getMessage()]],
        ];
    }
}

