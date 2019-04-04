<?php
namespace Interkassa\Config;
use \RS\Orm\Type as OrmType;

/**
 * Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
 */
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('payment.gettypes');
    }

    /**
     * Добавляем новый вид оплаты - Интеркасса
     *
     * @param array $list - массив уже существующих типов оплаты, который собирается со всей системы
     * @return array
     */
    public static function paymentGetTypes($list)
    {
        $list[] = new \Interkassa\Model\PaymentType\Interkassa(); //Интеркасса. Класс который мы создадим и будет обрабатывать все запросы к интеркассе.
        return $list;
    }
}