<?php
namespace app\modules\user\components;

use app\modules\user\forms\ProfileForm;
use app\modules\user\forms\ProfileRegistrationForm;
use app\modules\user\forms\RegistrationForm;
use app\modules\user\helpers\EmailConfirmStatusHelper;
use app\modules\user\helpers\ModuleTrait;
use app\modules\user\helpers\Password;
use app\modules\user\helpers\RegisterFromHelper;
use app\modules\user\helpers\UserManagerEventHelper;
use app\modules\user\helpers\UserStatusHelper;
use app\modules\user\models\IdentityUser;
use app\modules\user\models\query\AccessQuery;
use app\modules\user\models\query\UserQuery;
use app\modules\user\models\RegisterUser;
use app\modules\user\models\User;
use app\modules\user\models\Access;
use app\modules\user\models\Token;
use Yii;
use yii\base\Component;
use yii\db\Expression;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;

/**
 * Компонент для управления пользовательскими данными
 *
 * Class UserManager
 * @package app\modules\user\components
 *
 *
 */
class UserManager extends Component {

    const EVENT_SUCCESS_REGISTRATION = 'user.success.registration';

    const EVENT_SUCCESS_REGISTRATION_NEED_ACTIVATION = 'user.success.registration.need.activation';

    const EVENT_RECOVERY_PASSWORD = 'user.recovery.password';

    const EVENT_GENERATE_PASSWORD = 'user.generate.password';

    const EVENT_CHANGE_PASSWORD = 'user.change.password';

    const EVENT_CHANGE_EMAIL = 'user.change.email';

    use ModuleTrait;
    use UserManagerEventHelper;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var AccessQuery
     */
    protected $accessQuery;


    /**
     * @var UserQuery
     */
    protected $userQuery;


    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init() {

        parent::init();

        $this->userQuery = User::find();

        $this->accessQuery = Access::find();

        /** @var $tokenStorage TokenStorage */
        $tokenStorage = Yii::createObject(['class'=>TokenStorage::className()]);

        $this->setTokenStorage($tokenStorage);

        $this->setListener();
    }

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    /**
     * @param $usernameOrEmail
     * @return User|IdentityUser|null|\yii\db\ActiveRecord
     */
    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        return IdentityUser::find()
            ->findUser('username = :user OR email = :user', [':user' => $usernameOrEmail])
            ->one();
    }


    /**
     * @param $id
     * @return User|array|null
     */
    public function findUserById($id) {

        return $this->userQuery
            ->findUser('id = :id', [':id' => (int) $id])
            ->one();
    }


    /**
     * Регистрация нового пользователя через Форму
     *
     * @param RegistrationForm $model
     * @param ProfileRegistrationForm $profile
     * @return bool
     * @throws \yii\db\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function registerForm(RegistrationForm $model, ProfileRegistrationForm $profile) {

        $user = $this->createUserForRegistration($model);

        $user->setAttributes($profile->getAttributes());

        $transaction  = app()->db->beginTransaction();

        if (!$user->save()) $this->failureTransaction($transaction);

        if (!$this->module->emailAccountVerification) {

            $this->trigger(
                self::EVENT_SUCCESS_REGISTRATION,
                $this->getUserEvent($user)
            );

        } else {

            $this->trigger(
                self::EVENT_SUCCESS_REGISTRATION_NEED_ACTIVATION,
                $this->getUserTokenEvent($user, $this->tokenStorage->createAccountActivationToken($user))
            );
        }

        return $this->successTransaction($transaction);
    }


    /**
     * @param User $user
     *
     * @return User
     */
    protected function setUserStatusTypeAndEmailConfirm(User $user) {

        if (!$this->module->emailAccountVerification) {

            $user->status = UserStatusHelper::STATUS_ACTIVE;
            $user->email_confirm = EmailConfirmStatusHelper::EMAIL_CONFIRM_YES;
        } else {

            $user->status = UserStatusHelper::STATUS_NOT_ACTIVE;
            $user->email_confirm = EmailConfirmStatusHelper::EMAIL_CONFIRM_NO;
        }

        return $user;
    }


    /**
     * @param Transaction $transaction
     * @return bool
     *
     * @throws \yii\db\Exception
     */
    protected function failureTransaction(Transaction $transaction) {

        $transaction->rollBack();

        return false;
    }


    /**
     * @param Transaction $transaction
     * @return bool
     *
     * @throws \yii\db\Exception
     */
    protected function successTransaction(Transaction $transaction) {

        $transaction->commit();

        return true;
    }


    /**
     * @param RegistrationForm $model
     *
     * @return RegisterUser|User
     * @throws \yii\base\Exception
     */
    protected function createUserForRegistration(RegistrationForm $model) {

        $user = new RegisterUser();

        $user->setScenario(RegisterUser::SCENARIO_REGISTER);

        $user->setAttributes($model->getAttributes());

        $user = $this->setUserStatusTypeAndEmailConfirm($user);

        $user->hash = Password::hash($model->password);

        $user->registered_from = RegisterFromHelper::FORM;

        return $user;
    }


    /**
     * @param User $user
     *
     * @return bool
     */
    protected function updateUserSuccessStatus(User $user) {

        return (bool) $user->updateAttributes([
            'status'=>UserStatusHelper::STATUS_ACTIVE,
            'status_change_at'=>new Expression('NOW()'),
            'email_confirm'=>EmailConfirmStatusHelper::EMAIL_CONFIRM_YES,
        ]);
    }


    /**
     * @param User $user
     * @param $password
     * @return bool
     * @throws \yii\base\Exception
     */
    protected function updateUserHashPassword(User $user, $password) {

        return (bool) $user->updateAttributes(['hash'=>Password::hash($password)]);
    }


    /**
     * Проверка Email
     *
     * @param string $token
     * @param integer $tokenType
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function verifyEmail($token, $tokenType) {

        /* @var User $user */
        /* @var Token $tokenModel */
        list($tokenModel, $user) = $this->getTokenUserList($token, $tokenType);

        if ($user === null || $tokenModel === null)  return false;

        $transaction = app()->db->beginTransaction();

        if (!$this->updateUserSuccessStatus($user)) return $this->failureTransaction($transaction);

        if (!$this->tokenStorage->delete($tokenModel)) return $this->failureTransaction($transaction);

        return $this->successTransaction($transaction);
    }


    /**
     * @param $token
     * @param $tokenType
     * @return array list(UserToken, User)
     */
    public function getTokenUserList($token, $tokenType) {

        $tokenModel =  $this->tokenStorage->getToken($token, $tokenType);

        if ($tokenModel === null) return [null, null];

        return [$tokenModel, $this->findUserById($tokenModel->user_id)];
    }



    /**
     * @param User $user
     *
     * @return array
     */
    public function getAccessForUser(User $user) {

        if ($user === null) return [];

        $data = $this->accessQuery->getDataForUser($user->id);

        if ($user->isUFAccessLevel()) {

            $data = ArrayHelper::merge($data, $this->accessQuery->getDataForRole($user->access_level));
        }

        return $data;
    }


    /**
     * @param User $user
     * @param Token $token
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function generatePassword(User $user, Token $token) {

        $password = Password::generate(8);

        $transaction = app()->db->beginTransaction();

        if (!$this->updateUserHashPassword($user, $password)) return $this->failureTransaction($transaction);

        $this->trigger(self::EVENT_GENERATE_PASSWORD, $this->getUserPasswordEvent($user, $password));

        if (!$this->tokenStorage->delete($token)) return $this->failureTransaction($transaction);

        return $this->successTransaction($transaction);
    }


    /**
     * Восстановление пароля, отправка токена смены пароля
     *
     * @param User $user
     *
     * @return bool
     * @throws \yii\db\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function recoverySendMail(User $user) {

        if (null === $user) return false;

        $transaction = app()->getDb()->beginTransaction();

        $token = $this->tokenStorage->createPasswordToken($user);

        if ($token === null) return $this->failureTransaction($transaction);

        $this->trigger(self::EVENT_RECOVERY_PASSWORD, $this->getUserTokenEvent($user, $token));

        return $this->successTransaction($transaction);
    }


    /**
     * @param User $user
     * @param Token $token
     * @param string $password
     *
     * @return bool
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function changePassword(User $user, Token $token, $password) {

        $transaction = app()->db->beginTransaction();

        if (!$this->updateUserHashPassword($user, $password)) return $this->failureTransaction($transaction);

        if (!$this->tokenStorage->delete($token)) return $this->failureTransaction($transaction);

        $this->trigger(self::EVENT_CHANGE_PASSWORD, $this->getUserEvent($user));

        $transaction->commit();

        return true;
    }


    /**
     * @param string $password
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function changePasswordProfile($password) {

        return $this->updateUserHashPassword(app()->user->info, $password);
    }


    /**
     * @param User $user
     * @param string $email
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\base\Exception
     */
    public function changeEmail(User $user, $email) {

        if ($email==$user->email && $user->isConfirmedEmail()) return true;

        $transaction = app()->db->beginTransaction();

        $user = $this->setUserStatusTypeAndEmailConfirm($user);
        $user->email = $email;

        if (!$user->save()) return $this->failureTransaction($transaction);

        if (!$this->module->emailAccountVerification) return $this->successTransaction($transaction);

        $token = $this->tokenStorage->createEmailActivationToken($user);

        if ($token === false) return $this->failureTransaction($transaction);

        $this->trigger(self::EVENT_CHANGE_EMAIL, $this->getUserTokenEvent($user, $token));

        return $this->successTransaction($transaction);
    }


    /**
     * @param ProfileForm $model
     *
     * @return bool
     */
    public function saveProfile(ProfileForm $model) {

        $data = $model->getAttributes();

        $data['avatar'] = $data['avatar_file'];

        unset($data['avatar_file']);
        unset($data['email']);

        return user()->info->updateAttributes($data);
    }
}