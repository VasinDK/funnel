<?php

declare(strict_types=1);

namespace Dk\Vasin\Models;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;

class FunnelMonitorTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vasin_funnel_monitor';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID',  [
                'autocomplete' => true,
                'primary' => true,
            ]),
            new IntegerField('DEAL_CATEGORY_ID',  [
                'required' => true,
            ]),
            new IntegerField('DAYS_DELAY',  [
                'required' => true,
            ]),
            new DatetimeField('CREATED_AT', [
                'required' => false,
            ]),
            new DatetimeField('UPDATED_AT', [
                'required' => false,
            ]),
        ];
    }

    /**
     * Стирает таблицу
     *
     * @return Result
     * @throws SqlQueryException
     */
    public static function clean(): Result
    {
        $connection = Application::getInstance()->getConnection();
        $tableName = self::getTableName();
        $res = $connection->Query('TRUNCATE TABLE ' . $tableName . ';');
        static::cleanCache();

        return $res;
    }
}
