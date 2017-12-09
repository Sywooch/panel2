<?php
/**
 * Created by PhpStorm.
 * User: pastet
 * Date: 09.12.2017
 * Time: 11:49
 */

namespace app\modules\core\components;


use app\modules\core\helpers\ModulePriority;
use FilesystemIterator;
use iiifx\cache\dependency\FolderDependency;
use Yii;
use yii\base\Component;
use yii\caching\ChainedDependency;
use yii\caching\FileDependency;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;

class ModuleManager extends Component {

    private $_all_modules = null;


    private $_disabled_modules = null;


    private $_enabled_modules = null;


    /**
     * Определение всех модулей системы зависимых от приложения
     */
    private function _initAllModules() {

        $modules = cache()->get('all_modules');
        $modules = false;

        if ($modules === false) {

            $modules = $this->_scanModules();

            $chain = new ChainedDependency();

            $chain->dependencies = [

                new FileDependency(['fileName'=>Yii::getAlias('@app/config/web.php')]),
                new FileDependency(['fileName'=>Yii::getAlias('@app/config/console.php')]),
                new FolderDependency(['folder' => Yii::getAlias('@app/config/modules')]),
                new FolderDependency(['folder' => Yii::getAlias('@app/modules')]),
            ];

            cache()->set('all_modules', $modules, 3600, $chain);
        }

        $this->_all_modules = $modules;
    }


    /**
     * Определение неактивных (неустановленных) модулей в системе
     */
    private function _initDisabledModules() {

        $modules = cache()->get('disabled_modules');

        if ($modules === false) {

            $modules = $this->getAllModules();
            $enabled_modules = $this->getEnabledModules();

            foreach ($modules as $module => $moduleData) {

                if (isset($enabled_modules[$module])) {

                    unset($modules[$module]);
                }
            }

            $chain = new ChainedDependency();

            $chain->dependencies = [
                new TagDependency(['tags'=>['all_modules', 'enabled_modules']])
            ];

            cache()->set('disabled_modules', $modules, 3600, $chain);
        }

        $this->_disabled_modules = $modules;
    }


    /**
     * Определение установленных модулей, зависимых от приложения
     */
    private function _initEnabledModules() {

        $modules = cache()->get('enabled_modules');

        if ($modules === false) {

            $modules = $this->_scanEnabledModules();

            $chain = new ChainedDependency();

            $chain->dependencies = [
                new TagDependency(['tags'=>['all_modules']]),
                new FolderDependency(['folder' => Yii::getAlias('@app/config/modules')]),
                new FileDependency(['fileName'=>ModulePriority::model()->getFile()])
            ];

            app()->cache->set('enabled_modules', $modules, 3600, $chain);
        }

        $this->_enabled_modules = $modules;
    }


    /**
     * Сканирование установленных модулей системы
     *
     * @return array
     */
    private function _scanEnabledModules() {

        $modules = [];
        $allModules = $this->getAllModules();

        if (count(app()->getModules())) {

            $counter = 1;
            foreach (app()->getModules() as $key => $value) {

                if (!isset($allModules[$key])) continue;
                if (!($value instanceof \app\modules\core\components\ModuleParamsInterface)) continue;


                $data = ArrayHelper::merge($allModules[$key],
                    [
                        'priority' => $allModules[$key]['is_system']?($counter++):ModulePriority::model()->$key,
                        'paramsCounter' => count(app()->getModule($key)->getParamLabels()),
                    ]);

                $modules[$key] = $data;
            }

            $modules = $this->_sortingModules($modules);
        }

        return $modules;
    }


    /**
     * Сканирование "@app/modules" на наличие модулей системы
     *
     * @return array
     */
    private function _scanModules() {

        $modules = [];
        $dependentModules = [];

        $modulesPath = Yii::getAlias('@app/modules');

        /* @var \SplFileInfo $item */
        foreach (new FilesystemIterator(Yii::getAlias('@app/modules')) as $item) {

            $moduleName = $item->getBaseName();

            if (!is_dir($modulesPath. DIRECTORY_SEPARATOR. $moduleName)) continue;

            $classObject = 'Module';

            if (!file_exists($item->getRealPath().DIRECTORY_SEPARATOR.$classObject.'.php'))
                continue;

            $isInstallConfig = file_exists(implode(DIRECTORY_SEPARATOR, [
                $item->getRealPath(),
                'install',
                'config.php',
            ]));

            $classObject = '\\app\\modules\\'.$moduleName.'\\'.$classObject;

            $reflection = new \ReflectionClass($classObject);

            /* @var \app\modules\core\components\ModuleSettingsInterface $classObject */
            if (!$reflection->implementsInterface('\app\modules\core\components\ModuleSettingsInterface')) continue;

            foreach ($classObject::dependsOnModules() as $d_module) {

                $dependentModules[$d_module][] = $moduleName;
            }

            $modules[$moduleName] = [
                'title'=>$classObject::Title(),
                'is_system'=>!$isInstallConfig,
                'dependsOn'=>$classObject::dependsOnModules(),//зависит от модулей
                'dependent'=>  [], //зависимые модули
            ];
        }

        foreach($modules as $module => $temp) {

            if (isset($dependentModules[$module])) {

                $modules[$module]['dependent'] = $dependentModules[$module];
            }
        }

        return $modules;
    }



    /**
     * Сортировка модулей по параметру
     *
     * @param array $modules
     *
     * @return array
     */
    private function _sortingModules($modules)
    {
        $sort = [];

        foreach ($modules as $module => $data) {

            $sort[$module] = $data['priority'];
        }

        asort($sort);

        $data = [];
        foreach ($sort as $module => $temp) {

            $data[$module] = $modules[$module];
        }

        return $data;
    }


    public function getAllModules() {

        if ($this->_all_modules === null) $this->_initAllModules();

        return $this->_all_modules;
    }


    public function getDisabledModules() {

        if ($this->_disabled_modules === null) $this->_initDisabledModules();

        return $this->_disabled_modules;
    }


    public function getEnabledModules() {

        if ($this->_enabled_modules === null) $this->_initEnabledModules();

        return $this->_enabled_modules;
    }


    public function init() {

        cache()->flush();

        parent::init();
    }

}