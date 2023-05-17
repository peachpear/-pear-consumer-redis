<?php

namespace console\modules\script\controllers;

use console\components\BaseController;
use yii;

class SiteController extends BaseController
{
    public function actionIndex($consumer)
    {
        Yii::error('abc123');
        throw new \Exception('def456');
        echo $consumer;
    }
}