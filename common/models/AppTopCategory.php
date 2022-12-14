<?php

namespace common\models;

use Yii;
/**
 * This is the model class for table "app_top_category".
 *
 * @property int $id
 * @property int $category
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
            [['category','position'], 'integer'],
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
            'position' => 'Position',
            'date' => 'Date',
        ];
    }
}
