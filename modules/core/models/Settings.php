<?php

namespace app\modules\core\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%core_settings}}".
 *
 * @property integer $id
 * @property string $module
 * @property string $param_name
 * @property string $param_value
 * @property integer $user_id
 * @property string $created_at
 * @property string $updated_at
 */
class Settings extends \yii\db\ActiveRecord
{
    const USER_DATA = 'user_data';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%core_settings}}';
    }


    public function beforeSave() {

        if ($this->module === self::USER_DATA) {

            $this->user_id  = user()->id;
        }

        if (user()->isGuest) return false;

        return parent::beforeSave($this->isNewRecord);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['param_name', 'param_value'], 'required'],
            [['user_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['module'], 'string', 'max' => 50],
            [['param_name'], 'string', 'max' => 100],
            [['param_value'], 'string', 'max' => 500],
            [['module', 'param_name', 'user_id'], 'unique', 'targetAttribute' => ['module', 'param_name', 'user_id'], 'message' => 'The combination of Module, Param Name and User ID has already been taken.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'module' => 'Модуль',
            'param_name' => 'Название параметра',
            'param_value' => 'Значение параметра',
            'user_id' => 'Пользователь',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата изменения',
        ];
    }


    public static function findAllModuleData() {

        $temp = self::find()
           ->select('module', 'param_name', 'param_value')
           ->where('module != :module', [':module'=>self::USER_DATA])
           ->asArray()
           ->all();

        $data = [];
        foreach ($temp as $v) {

            $data[$v['module']][$v['param_name']] = $v['param_value'];
        }

        return $data;
    }


    public static function findAllUserData() {

        $temp = self::find()
            ->select('param_name', 'param_value')
            ->where('module = :module and user_id = :user', [':module'=>self::USER_DATA, ':user'=>(int) user()->id])
            ->asArray()
            ->all();

        return ArrayHelper::map($temp, 'param_name', 'param_value');
    }


    public static function saveUserData($name, $value) {

        $model = self::findOne('module=:module AND param_name=:name AND user_id=:user', [':name'=>$name, ':module'=>self::USER_DATA, ':user'=>user()->id]);

        if ($model === null) {

            $model = new self;
            $model->module = self::USER_DATA;
            $model->param_name = $name;
        }

        $model->param_value = $value;

        return $model->save();
    }
}