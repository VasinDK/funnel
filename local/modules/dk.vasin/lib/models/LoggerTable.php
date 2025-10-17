<?php

declare(strict_types=1);

namespace Dk\Vasin\Models;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;

class LoggerTable extends DataManager
{

    public static function getTableName(): string
    {
        return 'vasin_logger';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID',  [
                'autocomplete' => true,
                'primary' => true,
            ]),
            new IntegerField('USER_ID',  [
                'required' => false,
            ]),
            new StringField('MESSAGE',  [
                'required' => true,
            ]),
            new DatetimeField('CREATED_AT', [
                'required' => true,
            ]),
        ];
    }
}
