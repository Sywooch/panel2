<?php
namespace app\modules\developer\auth;

use app\modules\user\components\RBACItem;
use app\modules\user\components\Roles;
use yii\rbac\Item;

/**
 * Задача Управление минрациями
 *
 * Class MigrationTask
 * @package app\modules\developer\auth
 */
class MigrationTask extends RBACItem {

    const TASK = '/developer/migration';

    const OPERATION_CREATE = '/developer/migration/create';

    const OPERATION_READ = '/developer/migration/index';

    const OPERATION_REFRESH = '/developer/migration/refresh';


    public $types = [
        self::TASK => Item::TYPE_ROLE,
        self::OPERATION_CREATE => Item::TYPE_PERMISSION,
        self::OPERATION_READ => Item::TYPE_PERMISSION,
        self::OPERATION_REFRESH => Item::TYPE_PERMISSION,

    ];


    /**
     * @return array
     */
    public function titleList()
    {
        return [
            self::TASK => 'Управление миграциями',
            self::OPERATION_CREATE => 'Создание миграции',
            self::OPERATION_READ => 'Просмотр списка миграций',
            self::OPERATION_REFRESH => 'Обновление БД',
        ];
    }


    /**
     * @return array
     */
    public function getTree()
    {

        return [
            Roles::ADMIN => [
                self::TASK,
            ],
            self::TASK => [
                self::OPERATION_READ,
                self::OPERATION_CREATE,
                self::OPERATION_REFRESH,
            ]
        ];
    }
}