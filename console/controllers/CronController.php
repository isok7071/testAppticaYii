<?php

namespace console\controllers;

use yii\console\Controller;

class CronController extends Controller{

    /** Консольная команда для обновления статистики приложения в топе
     * по категориям за каждую дату из месячного периода
     * @return void
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function actionUpdateAppTopCategory()
    {
        $appTopCategoryUpdater = new \common\models\AppTopCategoryUpdater();
        $appTopCategoryUpdater->updateAppTopCategoryTable();
    }
}
?>