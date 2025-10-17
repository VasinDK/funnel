<?php

declare(strict_types=1);

namespace Dk\Vasin\Helpers;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Type\DateTime;
use Dk\Vasin\Models\LoggerTable;

class Logger extends Base
{
    /**
     * Добавляет запись в лог
     *
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public static function add($message): bool
    {
        global $USER;

        $result = LoggerTable::add([
            'USER_ID'    => (int)$USER->getId(),
            'MESSAGE'    => $message,
            'CREATED_AT' => new DateTime(),
        ]);

        return $result->isSuccess();
    }
}
