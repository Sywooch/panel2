<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var string $fullName */

?>
<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    Здравствуйте, <?=$fullName?>
</p>
<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    На сайте "<?=app()->name?>" Вы успешно <b>изменили</b> пароль.
</p>
<p style="font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; font-weight: normal; margin: 0 0 10px; padding: 0;">
    <b>Адрес: </b><?= Html::a(Html::encode(Url::to(['/login'], 1)), Url::to(['/login'], 1))?>
</p>
