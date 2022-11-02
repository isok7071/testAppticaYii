<?php

namespace frontend\controllers;


use app\models\AppTopCategory;
use Yii;
use yii\httpclient\Client;
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
     * @param $date string Дата выборки
     * @return false|string
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function actionAppTopCategory($date)
    {
        //Логирование в файл /frontend/runtime/logs/app.log
        Yii::info('Прилетел запрос на endpoint: AppTopCategory');

        //Получаем параметр даты
        $requestedDate = Yii::$app->getRequest()->getQueryParam('date');
        //Проверяем соответствует ли полученный параметр валидной дате
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$requestedDate)) {
            return json_encode(array('status_code'=>400, 'message'=>'bad date'));
        }

        //Проверяем есть ли уже в базе данных запись
        $appTopCategoryModel = AppTopCategory::find()->where(['date'=>$date])->asArray()->all();

        //Если приходит такая создаем корректный json и возвращаем его
        $appTopCategoryResponse = array('status_code'=>200, 'message'=>'ok');
        if ($appTopCategoryModel){
            foreach ($appTopCategoryModel as $modelData) {
                $appTopCategoryResponse['data'][$modelData['category']] = (int)$modelData['position'];
            }
            return json_encode($appTopCategoryResponse);
        }

        return $this->getPackageTopHistoryByDate($requestedDate);
    }


    /**
     * Делает запрос на api Apptica, обрабатывает и записывает в БД
     * @param string $requestedDate Дата на которую делаем выборку
     * @return false|string
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    private function getPackageTopHistoryByDate($requestedDate = '')
    {
        $client = new Client(['baseUrl' => 'https://api.apptica.com/package/top_history/1421444/1']);
        $responseJson = $client->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders(['content-type' => 'application/json'])
            ->setData(['date_to' => $requestedDate])
            ->send();
        //Честно говоря не понял какую именно дату нужно подставить в запрос по тестовому заданию, поэтому оставил
        // один параметр date_to

        //Если задали неправильный параметр или допущена другая ошибка и тд, возвращаем массив с кодом 400
        if ($responseJson->statusCode != '200') return json_encode(array('status_code'=>400, 'message'=>'bad request'));

        //Преобразуем из json в ассоциативный массив для обрабокти
        $responseDecoded = json_decode($responseJson->content, true);

        //Если приходит массив без данных, возвращаем 400
        if ($responseDecoded['data']=='' || $responseDecoded['data']==null) return json_encode(array('status_code'=>400, 'message'=>'no data by this date')) ;

        //Задаем массив который будем возвращать по нашему апи в случае успеха в обработке
        $appTopCategory = array('status_code'=>200, 'message'=>'ok');


        //Обрабатываем полученный по апи массив и записываем в бд
        foreach ($responseDecoded['data'] as $categoryId=>$subcategories){
            foreach ($subcategories as $subcategory){
                if ($subcategory[$requestedDate] == '' || $subcategory[$requestedDate] == null) continue;
                //Если нет позиции по дате пропускаем итерацию
                $subcategoryPositions[$categoryId][] = $subcategory[$requestedDate];
                foreach ($subcategoryPositions as $positionByDate) {
                    //Добавляем в существующий массив и в бд, id родительских
                    // категорий и минимальное значение позиции в топе
                    $minPosition = min($positionByDate);
                    $appTopCategory['data'][$categoryId] = $minPosition;
                    $appTopCategoryModel = new AppTopCategory();
                    $appTopCategoryModel->category = $categoryId;
                    $appTopCategoryModel->position = $minPosition;
                    $appTopCategoryModel->date = $requestedDate;
                }
            }
            $appTopCategoryModel->save();
        }
        return json_encode($appTopCategory);
    }
}
