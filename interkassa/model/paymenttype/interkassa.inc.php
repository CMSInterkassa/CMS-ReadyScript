<?php

namespace Interkassa\Model\PaymentType;

use \RS\Orm\Type;
use \Shop\Model\Orm\Transaction;

/**
 * Способ оплаты - Interkassa
 */
class Interkassa extends \Shop\Model\PaymentType\AbstractType
{
    const
        API_URL = "https://sci.interkassa.com/", //URL Api для взаимодействия
        PAYWAYS_URL = "https://api.interkassa.com/v1/paysystem-input-payway", //URL Api для получения полниго списка типов оплат
        IP_DIAPOZON = "85.10.225."; //Диапозон IP адресов интеркассы с которых должны приходить запросы

    /**
     * Возвращает название расчетного модуля (типа доставки)
     *
     * @return string
     */
    function getTitle()
    {
        return t('Интеркасса');
    }

    /**
     * Возвращает описание типа оплаты. Возможен HTML
     *
     * @return string
     */
    function getDescription()
    {
        return t('Оплата через агрегатор платежей "Интеркасса"');
    }

    /**
     * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
     *
     * @return string
     */
    function getShortName()
    {
        return 'interkassa';
    }

    /**
     * Возвращает true, если данный тип поддерживает проведение платежа через интернет
     *
     * @return bool
     */
    function canOnlinePay()
    {
        return true;
    }

    /**
     * Отправка данных с помощью POST?
     *
     */
    function isPostQuery()
    {
        return true;
    }

    /**
     * Возвращает ORM объект для генерации формы или null
     *
     * @return \RS\Orm\FormObject | null
     */
    function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator(array(
            // new
            'api_id' => new Type\Varchar(array(
                'maxLength' => 255,
                'description' => t('User ID - Id пользователя'),
                'hint' => t('Указан в Личном кабинете, раздел API'),
            )),
            'api_key' => new Type\Varchar(array(
                'maxLength' => 255,
                'description' => t('Key - Ключ Api'),
                'hint' => t('Указан в Личном кабинете, раздел API'),
            )),
            //new
            'merchant_id' => new Type\Varchar(array(
                'maxLength' => 255,
                'description' => t('Checkout ID - индетификатор кассы'),
                'hint' => t('Указан на странице Вашей кассы'),
            )),
            'secret_key' => new Type\Varchar(array(
                'description' => t('Секретный ключ'),
                'hint' => t('Указан на странице настроек Вашей кассы'),
            )),
            'test_key' => new Type\Varchar(array(
                'description' => t('Тестовый ключ'),
                'hint' => t('Указан на странице настроек Вашей кассы')
            )),
            //new
            'enabled_API' => new Type\Integer(array(
                'maxLength' => '1',
                'description' => t('Использовать API'),
                'CheckBoxView' => array(1, 0),
//                'meVisible' => false,
            )),
            'test_mode' => new Type\Integer(array(
                'maxLength' => '1',
                'description' => t('Тестовый режим'),
                'CheckBoxView' => array(1, 0),
//                'meVisible' => false,
            )),
            //new
//            'language' => new Type\Varchar(array(
//                'maxLength' => 5,
//                'description' => t('Язык интерфейса'),
//                'listFromArray' => array(array(
//                    0 => t('Определяется Интеркассой'),
//                    'ru' => t('Русский'),
//                    'ua' => t('Украинский'),
//                    'en' => t('Английский'),
//                ))
//            )),
            '__help__' => new Type\MixedType(array(
                'description' => t(''),
                'visible' => true,
                'template' => '%interkassa%/form/payment/interkassa/help.tpl'
            )),
        ));

        return new \RS\Orm\FormObject($properties);
    }

    /**
     * Возвращает URL для перехода на сайт сервиса оплаты
     *
     * @param Transaction $transaction - ORM объект транзакции
     * @return string
     */
    function getPayUrl(\Shop\Model\Orm\Transaction $transaction)
    {
        $order = $transaction->getOrder(); //Данные о заказе
        /**
         * @var mixed
         */
        $user = $order->getUser(); //Пользователь который должен оплатить

        $inv_id = $transaction->id;
        $out_summ = round($transaction->cost, 2);
        $in_cur = $this->getPaymentCurrency();

        $url_modul = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

        //принудительно указываем метод post и url
        $router = \RS\Router\Manager::obj();

        $FormData = array(
            'ik_am' => $out_summ,
            'ik_cur' => $in_cur,
            'ik_co_id' => $this->getOption('merchant_id', ''),
            'ik_pm_no' => $inv_id,
            'ik_desc' => t("Оплата заказа №") . $order['order_num'],
            'ik_ia_u' => $router->getUrl('shop-front-onlinepay', array(
                'Act' => 'result',
                'PaymentType' => $this->getShortName(),
            ), true),
            'ik_suc_u' => $router->getUrl('shop-front-onlinepay', array(
                'Act' => 'success',
                'custom' => $inv_id,
                'PaymentType' => $this->getShortName(),
            ), true),
            'ik_fal_u' => $router->getUrl('shop-front-onlinepay', array(
                'Act' => 'fail',
                'custom' => $inv_id,
                'PaymentType' => $this->getShortName(),
            ), true),
        );
        if (!empty($this->getOption('test_mode', '')))
            $FormData['ik_pw_via'] = 'test_interkassa_test_xts';

        $FormData["ik_sign"] = $this->IkSignFormation($FormData, $this->getOption('secret_key', ''));
        $hidden_fields = '';
        foreach ($FormData as $key => $value) {
            $hidden_fields .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $enabled_API = $this->getOption('enabled_API', '');
        $merchant_id = $this->getOption('merchant_id', '');
        $api_id = $this->getOption('api_id', '');
        $api_key = $this->getOption('api_key', '');

        $url_style=\Setup::$CSS_PATH . '/interkassa.css';
        $url_img=\Setup::$IMG_PATH . '/interkassa-img/';
        $ajax_url=$FormData['ik_ia_u'];

        $url_location=$_SERVER['HTTP_REFERER'];

        include_once 'tpl.php';
    }

    //new
    public function ajaxSign_generate()
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
        header("Content-type: text/plain");
        $request = $_POST;


        if (isset($_POST['ik_act']) && $_POST['ik_act'] == 'process') {
            $request['ik_sign'] = $this->IkSignFormation($request, $this->getOption('secret_key', ''));
            $data = $this->getAnswerFromAPI($request);
        } else
            $data = $this->IkSignFormation($request, $this->getOption('secret_key', ''));

        return $data;
    }

    public function getAnswerFromAPI($data)
    {
        $ch = curl_init('https://sci.interkassa.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        echo $result;
        exit;
    }

    public function IkSignFormation($data, $secret_key)
    {
        if (!empty($data['ik_sign'])) unset($data['ik_sign']);

        $dataSet = array();
        foreach ($data as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $dataSet[$key] = $value;
        }

        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $arg = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($arg, true));

        return $ik_sign;
    }

    public function getIkPaymentSystems($ik_cashbox_id, $ik_api_id, $ik_api_key)
    {
        $username = $ik_api_id;
        $password = $ik_api_key;
        $remote_url = 'https://api.interkassa.com/v1/paysystem-input-payway?checkoutId=' . $ik_cashbox_id;

        // Create a stream
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode("$username:$password")
            )
        );

        $context = stream_context_create($opts);
        $response = file_get_contents($remote_url, false, $context);
        $json_data = json_decode($response);

        if (empty($response))
            return '<strong style="color:red;">Error!!! System response empty!</strong>';

        if ($json_data->status != 'error') {
            $payment_systems = array();
            if (!empty($json_data->data)) {
                foreach ($json_data->data as $ps => $info) {
                    $payment_system = $info->ser;
                    if (!array_key_exists($payment_system, $payment_systems)) {
                        $payment_systems[$payment_system] = array();
                        foreach ($info->name as $name) {
                            if ($name->l == 'en') {
                                $payment_systems[$payment_system]['title'] = ucfirst($name->v);
                            }
                            $payment_systems[$payment_system]['name'][$name->l] = $name->v;
                        }
                    }
                    $payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
                }
            }

            return !empty($payment_systems) ? $payment_systems : '<strong style="color:red;">API connection error or system response empty!</strong>';
        } else {
            if (!empty($json_data->message))
                return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
            else
                return '<strong style="color:red;">API connection error or system response empty!</strong>';
        }
    }

    //new

    /**
     * Получает все варианты оплаты
     *
     * @param string $checkout_id - id кассы
     */
    function getPayways()
    {
        $params = array();

        $params['ik_co_id'] = $this->getOption('ik_co_id', '');
        $params['ik_pm_no'] = 'PAYWAYS_1';
        $params['ik_am'] = 1;
        $params['ik_desc'] = 'Look up my payways';
        $params['ik_act'] = 'payways';
        $params['ik_int'] = 'json';
        $params['ik_cur'] = $this->getPaymentCurrency();
        $params['ik_sign'] = $this->getParamsSign($params);

        // Create a stream
        $opts = array(
            'http' => array(
                'method' => "GET",
            )
        );

        $context = stream_context_create($opts);
        $params = http_build_query($params);
        // Получим оплаты, которые привязаны к кассе
        $data = json_decode(file_get_contents(self::API_URL . "?" . $params, false, $context));
        // Подготовим массив
        $payways = array(0 => t('-Все типы оплат-'));

        if (isset($data->resultMsg) && $data->resultMsg == "Success") { //Если всё прошло успешно и мы получили типы путей оплаты
            foreach ($data->resultData->paywaySet as $way) {
                $payways[$way->als] = mb_strtoupper($way->ser) . " - " . mb_strtoupper($way->curAls);
            }
        }
        asort($payways);

        return $payways;
    }

    /**
     * Получает нужную подпись для разных режимов
     *
     * @param boolean $test - флаг, что нужно использовать тестовый ключ
     */
    private function getRightSign($test = false)
    {
        if ($test && ($this->getOption('ik_pw_via', false) == "test_interkassa_test_xts")) {
            file_put_contents(__DIR__ . '/file.txt', date('Y-m-d H:i:s') . "\n" . "345435\n", FILE_APPEND);
            return $this->getOption('test_key', '');
        }
        return $this->getOption('secret_key', '');
    }

    /**
     * Получает подпись для платежа формируемая по правилам Интеркассы
     *
     * @param array $params - массив параметров
     * @param boolean $test - флаг, что нужно использовать тестовый ключ
     */
    private function getParamsSign($params, $test = false)
    {
        unset($params['ik_sign']); //Удаляем из данных строку подписи
        ksort($params, SORT_STRING); //Сортируем по ключам в алфовитном порядке
        array_push($params, $this->getRightSign($test)); //Конкатинируем символом ":"
        return base64_encode(md5(implode($params, ":"), true)); //MD5 в бинарном виде в кодированном base64
    }

    /**
     * Получает язык в котором будет представлен интерфейс Интеркассы
     */
    private function getLanguage()
    {
        return $this->getOption('language', 0) ? $this->getOption('language', 0) : false;
    }

    /**
     * Получает трех символьный код базовой валюты в которой ведётся оплата
     *
     */
    private function getPaymentCurrency()
    {
        /**
         * @var \Catalog\Model\Orm\Currency
         */
        $currency = \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Currency())
            ->where(array(
                'public' => 1,
                'is_base' => 1,
            ))
            ->object();
        if (empty($currency)) {
            $mes['error'] = 'Не получены валюты от магазина';
            return $mes;
        }

        //new
        $remote_url_ik = 'https://api.interkassa.com/v1/currency';
        $cur_ik = $this->getData($this->api_id, $this->api_key, $remote_url_ik);
        if (empty($cur_ik)) {
            $mes['error'] = 'Не получены валюты от Интеркассы';
            return $mes;
        }

        $cur_for_mes = '';
        foreach ($cur_ik->data as $key => $item) {
            if ($currency->title == $key) {
                return $key;
            } elseif ($currency->title == 'RUR') {
                return 'RUB';
            } else {
                $cur_for_mes .= $key . ' ';
            }
        }

        $mes['error'] = 'Интеркасса не поддерживает валюту магазина. Доступные валюты: ' . $cur_for_mes;
        return $mes;
        //new
    }

    //new
    public function getData($login, $pass, $url)
    {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode($login . ':' . $pass)
            )
        );

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $json_data = json_decode($response); // оплачиваемый заказ
        return $json_data;
    }
    //new


    /**
     * Проверяем подпись запроса
     *
     * @param string $sign - подпись запроса
     */
    private function checkSign($sign, $test = false)
    {
        $ik = array();
        foreach ($_REQUEST as $key => $value) {
            if (stripos('ik_') !== false) {
                $ik[$key] = $value;
            }
        }
        $my_sign = $this->getParamsSign($ik, $test); //Получаем нами сформированную подпись
        // Проверка корректности подписи
        return $my_sign == $sign;
    }

    /**
     * Проверяет основные параметры приходящие от интеркассы сравнивая с теми, что уснавлены в настройках системы
     *
     * @param \Shop\Model\Orm\Transaction $transaction - объект транзакции
     * @param \RS\Http\Request $request - объект запросов
     */
    private function checkMainParams(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        $ik_co_id = $request->request('ik_co_id', TYPE_STRING, '');
        $ik_am = $request->request('ik_am', TYPE_STRING, '');
        $ik_inv_st = $request->request('ik_inv_st', TYPE_STRING, '');
        $ik_sign = $request->request('ik_sign', TYPE_STRING, '');

        if ($ik_co_id != $this->getOption('ik_co_id', '')) {
            return 'ID кассы';
        }
        if ($ik_am != round($transaction->cost, 2)) {
            return 'Сумма платежа';
        }
        if (!in_array($ik_inv_st, array('process', 'success', 'fail', 'waitAccept'))) {
            return 'параметр ik_inv_st=' . $ik_inv_st;
        }
        $ik = array();
        foreach ($_REQUEST as $key => $value) {
            if (stripos('ik_') !== false) {
                $ik[$key] = $value;
            }
        }

        $test = ($request->request('ik_pw_via', TYPE_STRING, '') == "test_interkassa_test_xts");
        if (!$this->checkSign($ik_sign, $test)) {
            return 'Неправильная подпись. Параметр ik_sign=' . $ik_sign . " " . $this->getParamsSign($ik);
        }
        return true;
    }

    /**
     * Возвращает ID заказа исходя из REQUEST-параметров соотвествующего типа оплаты
     * Используется только для Online-платежей
     *
     * @return mixed
     */
    function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        return $request->request('ik_pm_no', TYPE_INTEGER, false);
    }

    /**
     * Обработка запросов от интеркассы
     *
     * @param \Shop\Model\Orm\Transaction $transaction - объект транзакции
     * @param \RS\Http\Request $request - объект запросов
     * @return string
     */
    function onResult(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        if (!$this->checkMainParams($transaction, $request)) {
            $exception = new \Shop\Model\PaymentType\ResultException(t('Главные параметры указаные в настройках интеркассы не прошли проверку'));
            $exception->setResponse('Wrong main params');
            throw $exception;
        }

        //Смотрим текущий статус
        $status = $request->request('ik_inv_st', TYPE_STRING, 0);

        switch ($status) {
            //new
            case "0":
                echo $this->ajaxSign_generate();
                $exception = new \Shop\Model\PaymentType\ResultException(t('Не совершённ платёж'));
                $exception->setUpdateTransaction(false);
                throw $exception;
                break;
                //new
            case "success":
                return 'OKs' . $transaction->id;
                break;
            case "process":
                $exception = new \Shop\Model\PaymentType\ResultException(t('Неудачно совершённый платёж'));
                $exception->setResponse('Payment status progress');
                $exception->setUpdateTransaction(false);
                throw $exception;
                break;
            case "waitAccept": //Если долгое ожидание проведения платежа, то пользователь перенаправляется к нам
                \RS\Application\Application::getInstance()->headers->addHeader('Location', $request->getSelfAbsoluteHost());
                break;
            case "fail":
            default:
                $exception = new \Shop\Model\PaymentType\ResultException(t('Неудачно совершённый платёж'));
                $exception->setResponse('Payment failed');
                throw $exception;
                break;
        }
    }

    /**
     * Вызывается при открытии страницы неуспешного проведения платежа
     * Используется только для Online-платежей
     *
     * @param \Shop\Model\Orm\Transaction $transaction
     * @param \RS\Http\Request $request
     * @return void
     */
    function onFail(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        $transaction['status'] = $transaction::STATUS_FAIL;
        $transaction->update();
    }

}