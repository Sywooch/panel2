<?php

namespace app\controllers;

use app\modules\user\helpers\UserSettings;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions'=>['captcha'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'app\modules\core\components\actions\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $this->redirect(Url::to(app()->getModule('user')->profilePage));

        app()->end();
    }

    
	 public function actionSkins($skin = null) {

        if ($skin !== null) {

            UserSettings::model()->skinTemplate = $skin;
        }
    }


    public function actionSidebar($sidebar) {

        if (in_array($sidebar, ['remove', 'add'])) {

            UserSettings::model()->sideBar = $sidebar=='add'?'sidebar-collapse':"s";
        }
    }
}
