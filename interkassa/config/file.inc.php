<?php
namespace Interkassa\Config;
use \RS\Orm\Type;

/**
 * Конфигурационный файл модуля
 */
class File extends \RS\Orm\ConfigObject
{

    /**
     * Возвращает значения свойств модуля по-умолчанию
     *
     * @return array
     */
    public static function getDefaultValues()
    {
        return array(
            'name' => t('Интеркасса'), //Название нашего модуля
            'description' => t('Платёжная система - Интеркасса'), //Описание модуля
            'version' => '1.0.0.0', //Версия вашего модуля
            'author' => 'Сведения об авторе', //Сведения об авторе
        );
    }

}