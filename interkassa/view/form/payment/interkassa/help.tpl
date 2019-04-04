<h3>Настройка аккаунта Интеркасса</h3>


<p>Укажите алгоритм подписи у Вашей кассы - <b>MD5</b></p>

<p>Укажите эти URL в настройках Вашей кассы:</p>

<b>URL ожидания проведения платежа: </b><br>
<a target="_blank" href="{$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()], true)}">
    {$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()], true)}
</a>

<br><br>

<b>URL взаимодействия платежа: </b><br>
<a target="_blank" href="{$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()], true)}">
    {$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()], true)}
</a>

<br><br>

<b>URL успешной оплаты: </b><br>
<a target="_blank" href="{$router->getUrl('shop-front-onlinepay', [Act=>success, PaymentType=>$payment_type->getShortName()], true)}">
    {$router->getUrl('shop-front-onlinepay', [Act=>success, PaymentType=>$payment_type->getShortName()], true)}
</a>

<br><br>

<b>URL неуспешной оплаты: </b><br>
<a target="_blank" href="{$router->getUrl('shop-front-onlinepay', [Act=>fail, PaymentType=>$payment_type->getShortName()], true)}">
    {$router->getUrl('shop-front-onlinepay', [Act=>fail, PaymentType=>$payment_type->getShortName()], true)}
</a>

<p>Укажите методы для всех URL - <b>POST</b></p>