<?php

namespace app\models;

use DateInterval;
use DatePeriod;
use DateTime;
use Yii;
use yii\httpclient\Client;
use yii\httpclient\Exception;


class AppTopCategoryUpdater extends AppTopCategory
{
    public static function getUpdatePeriod()
    {
        //TODO Вынести можно в отдельную функцию
        $endDate = date('Y-m-d'); //Текущая дата
        $endDateModified =  DateTime::createFromFormat('Y-m-d', $endDate);
        $startDate = $endDateModified->modify('-1 month')->format('Y-m-d'); //Текущая дата

        //Создаем период по датам выборки статистики с api (так как статистика доступна за месяц)
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($endDate + 1),
            DatePeriod::EXCLUDE_START_DATE
        );
        //Формируем массив с датой в формате yyyy-mm-dd
        foreach ($period as $key => $value) {
            $appTopCategoryDatesPeriod[] = $value->format('Y-m-d');
        }
        return $appTopCategoryDatesPeriod;
    }

    public static function getPackageTopHistoryByMonth()
    {
        //Получаем массив дат
        $appTopCategoryDatesPeriod = self::getUpdatePeriod();
        $client = new Client(['baseUrl' => 'https://api.apptica.com/package/top_history/1421444/1']);
        $responseJson = $client->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders(['content-type' => 'application/json'])
            ->setData(['date_to' => end($appTopCategoryDatesPeriod)])
            ->send();
        //Сразу берем данные за месяц

        //Если задали неправильный параметр или допущена другая ошибка и тд, возвращаем массив с кодом 400
        if ($responseJson->statusCode != '200'){
            Yii::warning('Статистика не была обновлена, код не 200');
            throw new \Exception('Статистика не была обновлена, код не 200', 400);
        }

        //Преобразуем из json в ассоциативный массив для обработки
        $packageTopHistoryByMonth = json_decode($responseJson->content, true);

        //Если приходит массив без данных, возвращаем 400
        if ($packageTopHistoryByMonth['data']=='' || $packageTopHistoryByMonth['data']==null){
            Yii::warning('Статистика не была обнолена, так как массив пуст');
            throw new \Exception('Статистика не была обнолена, так как массив пуст');
        }

        return $packageTopHistoryByMonth;

    }

    /**
     * Делает запрос на api Apptica, обрабатывает и записывает в БД
     * @param string $requestedDate Дата на которую делаем выборку
     * @return false|string
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function updateAppTopCategoryTable()
    {
        try {
            $packageTopHistoryByMonth = self::getPackageTopHistoryByMonth();
        }catch (\Exception $e){
            Yii::warning('Таблица со статистикой не была обновлена');
            return;
        }

        //Обрабатываем полученный по апи массив и записываем в бд
        $appTopCategoryDatesPeriod = self::getUpdatePeriod();

        foreach ($packageTopHistoryByMonth['data'] as $categoryId=>$subcategories){
            foreach ($appTopCategoryDatesPeriod as $appTopCategoryDate) {
                foreach ($subcategories as $subcategory){
                    if ($subcategory[$appTopCategoryDate] == '' || $subcategory[$appTopCategoryDate] == null) continue;
                    //Если нет позиции по дате пропускаем итерацию
                    $subcategoryPositions[$categoryId][] = $subcategory[$appTopCategoryDate];
                    foreach ($subcategoryPositions as $positionByDate) {
                        //Добавляем в существующий массив и в бд, id родительских
                        // категорий и минимальное значение позиции в топе
                        $minPosition = min($positionByDate);
                        //Если запись существует обновляем ее
                        if ($appTopCategoryModelExist = AppTopCategory::find()->where(['date'=>$appTopCategoryDate])->andWhere(['category'=>$categoryId])->one()){
                            $appTopCategoryModelExist->position = $minPosition;
                        }else{
                            $appTopCategoryModel = new AppTopCategory();
                            $appTopCategoryModel->category = $categoryId;
                            $appTopCategoryModel->position = $minPosition;
                            $appTopCategoryModel->date = $appTopCategoryDate;
                        }
                    }
                }
                if ($appTopCategoryModel){
                    $appTopCategoryModel->save();
                }else{
                    $appTopCategoryModelExist->save();
                }
            }
        }
    }
}
