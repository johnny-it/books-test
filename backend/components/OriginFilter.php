<?php

namespace app\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

final class OriginFilter extends ActionFilter
{
    public function beforeAction($action): bool
    {
        $origin = Yii::$app->request->headers->get('Origin');
        $frontendOrigin = (string) Yii::$app->params['frontendOrigin'];

        if (!is_string($origin) || !hash_equals($frontendOrigin, $origin)) {
            throw new ForbiddenHttpException('Invalid request origin.');
        }

        return parent::beforeAction($action);
    }
}
