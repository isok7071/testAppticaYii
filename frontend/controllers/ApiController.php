<?php

namespace frontend\controllers;

use common\models\AppTopCategory;
use common\models\AppTopCategoryUpdater;
use Yii;
use yii\web\Controller;


/**
 * Api controller
 */
class ApiController extends Controller
{
    /**
     * В поведении ограничил запросы с 1 ip до 5 запросов в минуту
     * использовал thamtech/yii2-ratelimiter-advanced
     *
     * @return array[]
     */
    public function behaviors()
    {
        return [
            'rateLimiter' => [
                'class' => 'thamtech\ratelimiter\RateLimiter',
                'components' => [
                    'rateLimit' => [
                        'definitions' => [
                            'ip' => [
                                'limit' => 5, // allowed hits per window
                                'window' => 60, // window in seconds
                                'identifier' => function($context, $rateLimitId) {
                                    return $context->request->getUserIP();
                                }
                            ],
                        ],
                    ],
                    'allowanceStorage' => [
                        'cache' => 'cache', // use Yii::$app->cache component
                    ],
                ],
                'as rateLimitHeaders' => [
                    'class' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
                    'prefix' => ['X-Rate-Limit-', 'X-RateLimit-'],
                ],
                'as retryAfterHeader' => 'thamtech\ratelimiter\handlers\RateLimitHeadersHandler',
                'as tooManyRequestsException' => 'thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler',
            ]
        ];
    }

    /**
     * Endpoint для получение данных о позициях приложения
     * в топе по категориям за определенный день
     *
     * Если в локальной БД нет данных на данную дату, вызывает
     * getPackageTopHistoryByDate
     *
     * @param $date string Дата выборки пример: '2022-11-01'
     * @return false|string
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function actionAppTopCategory($date)
    {
        //Логирование в файл /frontend/runtime/logs/app.log
        Yii::info('Прилетел запрос на endpoint: AppTopCategory');

        //Получаем параметр даты
        $requestedDateParam = Yii::$app->getRequest()->getQueryParam('date');
        //Проверяем соответствует ли полученный параметр валидной дате
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $requestedDateParam)) {
            return json_encode(array('status_code'=>400, 'message'=>'bad date'));
        }elseif (!in_array($requestedDateParam, AppTopCategoryUpdater::getUpdatePeriod())){
            return json_encode(array('status_code'=>400, 'message'=>'the date must be no more than a month ago and no more than today'));
        }

        //Проверяем есть ли уже в базе данных запись
        $appTopCategoryModel = AppTopCategory::find()->where(['date'=>$date])->asArray()->all();

        //Если приходит такая создаем корректный json и возвращаем его
        $appTopCategoryResponse = array('status_code'=>200, 'message'=>'ok');
        if (!$appTopCategoryModel){
            $appTopCategoryUpdater = new AppTopCategoryUpdater();
            $appTopCategoryUpdater->updateAppTopCategoryTable();
            #Повторяем запрос
            $appTopCategoryModel = AppTopCategory::find()->where(['date'=>$date])->asArray()->all();
        }

        #Заполняем массив response данными из бд
        foreach ($appTopCategoryModel as $modelData) {
            $appTopCategoryResponse['data'][$modelData['category']] = (int)$modelData['position'];
        }
        return json_encode($appTopCategoryResponse);

    }
}
