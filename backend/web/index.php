<?php

defined('YII_DEBUG') or define('YII_DEBUG', filter_var(getenv('YII_DEBUG') ?: false, FILTER_VALIDATE_BOOL));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

$config = require dirname(__DIR__) . '/config/web.php';
(new yii\web\Application($config))->run();

