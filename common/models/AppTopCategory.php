<?php

namespace app\models;

use Yii;
use yii\httpclient\Client;

/**
 * This is the model class for table "app_top_category".
 *
 * @property int $id
 * @property int $category
 * @property int $subcategory
 * @property int|null $position
 * @property string $date
 */
class AppTopCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_top_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category', 'date'], 'required'],
            [['category','subcategory','position'], 'integer'],
            [['date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Category',
            'subcategory' => 'Subcategory',
            'position' => 'Position',
            'date' => 'Date',
        ];
    }
}
