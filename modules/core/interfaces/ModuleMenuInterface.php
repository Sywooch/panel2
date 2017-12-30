<?php
namespace app\modules\core\interfaces;

/**
 * Интерфейс модуля для работы меню приложения
 *
 * Interface ModuleMenuInterface
 * @package app\modules\core\components
 */
interface ModuleMenuInterface {

    public function getMenuAdmin();


    public function getMenuMain();


    public function getMenuRedactor();


}