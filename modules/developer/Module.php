<?php
namespace app\modules\developer;

use app\modules\core\components\Module as ParentModule;
use app\modules\developer\auth\CreateAuthTask;
use app\modules\developer\auth\MigrationTask;
use app\modules\user\components\Roles;

/**
 * Class Module
 * @package app\modules\developer
 */
class Module extends ParentModule
{
    /**
     * @return string
     */
    public static function Title() {

        return 'Разработка';
    }


    /**
     * @return array
     */
    public function getMenuAdmin() {

        return [
            [
                'label' => '<span class="hidden-xs">Разработка</span>',
                'icon' => 'fa fa-fw fa-wrench',
                'items' => [
                    [
                        'label' => 'Миграции',
                        'visible' => user()->can(MigrationTask::TASK),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-list-alt',
                        'label' => 'Список миграций',
                        'url' => $this->getMenuUrl('migration/index'),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-plus',
                        'label' => 'Добавить миграцию',
                        'url' => $this->getMenuUrl('migration/create'),
                    ],
                    [
                        'label' => '',
                        'itemOptions' => [
                            'role' => 'separator',
                            'class' => 'divider',
                        ],
                    ],
                    /* ----- */
                    [
                        'label' => 'RBAC',
                        'visible' => user()->can(CreateAuthTask::TASK),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-list-alt',
                        'label' => 'Список задач',
                        'url' => $this->getMenuUrl('auth/index'),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-plus',
                        'label' => 'Добавить задачу',
                        'url' => $this->getMenuUrl('auth/create'),
                    ],
                    [
                        'label' => '',
                        'itemOptions' => [
                            'role' => 'separator',
                            'class' => 'divider',
                            'visible' => user()->can(CreateAuthTask::TASK),
                        ],
                    ],
                    /* ----- */
                    [
                        'label' => 'Генераторы',
                        'visible' => app()->hasModule('gii') && user()->can(Roles::ADMIN),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-plus',
                        'label' => 'Создать модуль',
                        'url' => ['/gii/module'],
                        'visible' => app()->hasModule('gii') && user()->can(Roles::ADMIN),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-plus',
                        'label' => 'Создать контроллер',
                        'url' => ['/gii/controller'],
                        'visible' => app()->hasModule('gii') && user()->can(Roles::ADMIN),
                    ],
                    [
                        'icon' => 'fa fa-fw fa-plus',
                        'label' => 'Создать модель',
                        'url' => ['/gii/model'],
                        'visible' => app()->hasModule('gii') && user()->can(Roles::ADMIN),
                    ],
                    [
                        'label' => '',
                        'itemOptions' => [
                            'role' => 'separator',
                            'class' => 'divider',
                            'visible' => app()->hasModule('gii'),
                        ],
                    ],
                    [
                        'icon' => 'fa fa-fw fa-trash',
                        'label' => 'Очистить кеш',
                        'url' => ['/core/module/flush'],
                    ],
                ],
                'visible' => app()->user->can([Roles::ADMIN]),
            ],
        ];
    }
}
