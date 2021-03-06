<?php
namespace app\modules\user\models\query;

use app\modules\user\helpers\UserAccessLevelHelper;
use app\modules\user\helpers\UserStatusHelper;
use app\modules\user\models\User;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class UserQuery
 * @package app\modules\user\models\query
 *
 * This is the ActiveQuery class for [[User]].
 * @see \app\modules\user\models\User
 */
class UserQuery extends ActiveQuery
{
    /**
     * @param null $db
     * @return array|User[]|ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return User|ActiveRecord|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }


    /**
     * @param $condition
     * @param array $params
     * @return $this
     */
    public function findUser($condition, $params = []) {

        return $this->where($condition, $params);
    }


    /**
     * @return int
     */
    public function findCountAdmin() {

        $data = $this
                ->select('COUNT(*) as cnt')
                ->active()
                ->andWhere(['access_level'=>UserAccessLevelHelper::LEVEL_ADMIN])
                ->asArray()
                ->one();

        return isset($data['cnt'])?(int) $data['cnt']:0;
    }

    /**
     * @return $this
     */
    public function active() {

        return $this->andWhere(['status'=>UserStatusHelper::STATUS_ACTIVE]);
    }


    /**
     * @param $email
     * @return User|array|null|ActiveRecord
     */
    public function email($email) {

        return $this->where('email = :email', [':email'=>$email])->active()->one();
    }
}
