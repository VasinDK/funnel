<?php

declare(strict_types=1);

namespace Dk\Vasin\Services;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Data\AddResult;
use Dk\Vasin\Models\NotificationRecipientTable;

class RecipientService
{
    private const CACHE_TTL = 86400;

    /**
     * Сервис возвращает наблюдателей за просроченными сделками
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getRecipients(): array
    {
        return NotificationRecipientTable::getList([
            'select' => [
                'ID',
                'USER_ID'
            ],
            'cache' => [
                'ttl' => static::CACHE_TTL,
            ],
        ])->fetchAll();
    }

    /**
     * Сервис добавляет / обновляет наблюдателей за просроченными сделками
     *
     * @param array $items
     * @return AddResult
     * @throws ArgumentException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function upsert(array $items): AddResult
    {
        NotificationRecipientTable::clean();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'USER_ID' => $item['userId'],
                'UPDATED_AT' => new DateTime(),
                'CREATED_AT' => new DateTime(),
            ];
        }

        if (empty($data)) {
            return new AddResult();
        }

        return NotificationRecipientTable::addMulti($data);
    }
}