<?php

namespace common\models;

use DateInterval;
use DatePeriod;
use DateTime;
use Yii;
use yii\httpclient\Client;

/**
 * Класс для обновления данных топа категориий в БД
 */
class AppTopCategoryUpdater extends AppTopCategory
{
    /**
     * Получить период дат для статистики приложения по категориям
     * @return array Период дат (начало: месяц назад -1 день, конец: сегодняшний день включительно)
     * @throws \Exception
     */
    public static function getUpdatePeriod(): array
    {
        $endDate = date('Y-m-d'); //Текущая дата
        $endDateModified = DateTime::createFromFormat('Y-m-d', $endDate);
        $startDate = $endDateModified->modify('-1 month')->format('Y-m-d');

        //Создаем период дат для выборки статистики с api (так как статистика доступна за месяц)
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            new DateTime($endDate + 1),
            DatePeriod::EXCLUDE_START_DATE
        );

        $appTopCategoryDatesPeriod = [];
        //Формируем массив с датами в формате yyyy-mm-dd
        foreach ($period as $key => $value) {
            $appTopCategoryDatesPeriod[] = $value->format('Y-m-d');
        }
        return $appTopCategoryDatesPeriod;
    }


    /**
     * Делает запрос на Api Apptica, получает статистику по категорям за месяц
     * @return mixed Ассоциативный массив с данными о статистике по категориям за месяц, либо exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     * @throws \Exception
     */
    public static function getPackageTopHistoryByMonth()
    {
        //Получаем период за который будеем обновлять
        $appTopCategoryDatesPeriod = self::getUpdatePeriod();

        $client = new Client(['baseUrl' => 'https://api.apptica.com/package/top_history/1421444/1']);
        $responseJson = $client->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders(['content-type' => 'application/json'])
            ->setData(['date_to' => end($appTopCategoryDatesPeriod)])//Сразу берем данные за прошедший месяц
            ->send();


        //Если пришел код НЕ 200, записываем в логи, выкидываем исключение
        if ($responseJson->statusCode != '200') {
            Yii::warning('Статистика не была обновлена, код не 200');
            throw new \Exception('Статистика не была обновлена, код не 200');
        }

        //Преобразуем json в ассоциативный массив для дальнейшней обработки
        $packageTopHistoryByMonth = json_decode($responseJson->content, true);

        //Если массив пуст, записывае в логи, выкидываем исключение
        if ($packageTopHistoryByMonth['data'] == '' || $packageTopHistoryByMonth['data'] == null) {
            Yii::warning('Статистика не была обнолена, так как массив пуст в ' . date('m/d/Y h:i:s a', time()));
            throw new \Exception('Статистика не была обнолена, так как массив пуст');
        }

        return $packageTopHistoryByMonth;
    }

    /** Обновляет таблицу с позициями приложения в топе
     * по категориям за каждую дату из месячного периода(текущая дата включительно - 1 месяц).
     * @return mixed
     * @throws \Exception
     */
    public function updateAppTopCategoryTable()
    {
        try {
            $packageTopHistoryByMonth = self::getPackageTopHistoryByMonth();
        } catch (\Exception $e) {
            //Если по каким то причинам не удалось получить статистику, записываем в логи
            Yii::warning('Таблица со статистикой не была обновлена в ' . date('m/d/Y h:i:s a', time()));
            return;
        }

        //Получаем период дат, по которым обрабатываем массив со статистикой
        $appTopCategoryDatesPeriod = self::getUpdatePeriod();

        //Обрабатываем массив полученный с Api Apptica
        foreach ($appTopCategoryDatesPeriod as $appTopCategoryDate) {
            $subcategoryPositions=[];
            foreach ($packageTopHistoryByMonth['data'] as $categoryId=>$subcategories){
                foreach ($subcategories as $subcategory){
                    //Если нет позиции по дате пропускаем итерацию
                    if ($subcategory[$appTopCategoryDate] == '' || $subcategory[$appTopCategoryDate] == null) continue;
                    //Позиции по датам
                    $subcategoryPositions[$categoryId][] = $subcategory[$appTopCategoryDate];
                    foreach ($subcategoryPositions as $positionByDate) {
                        //Вычисляем минимальную позицию по дате
                        $minPosition = min($positionByDate);
                        //Если запись в БД существует обновляем ее
                        if ($appTopCategoryModelExist = AppTopCategory::find()->where(['date'=>$appTopCategoryDate])->andWhere(['category'=>$categoryId])->one()){
                            $appTopCategoryModelExist->position = $minPosition;
                        }else{
                            //Иначе создаем новую модель и записываем данные
                            $appTopCategoryModel = new AppTopCategory();
                            $appTopCategoryModel->category = $categoryId;
                            $appTopCategoryModel->position = $minPosition;
                            $appTopCategoryModel->date = $appTopCategoryDate;
                        }
                    }
                }
                //Сохраняем данные
                if ($appTopCategoryModel){
                    $appTopCategoryModel->save();
                }else{
                    $appTopCategoryModelExist->save();
                }
            }
        }
        Yii::warning('Таблица со статистикой обновлена в ' . date('m/d/Y h:i:s a', time()));
        return;
    }
}
