<?php

declare(strict_types=1);

namespace Dk\Vasin\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Dk\Vasin\Models\NotificationRecipientTable;

class Recipient extends Controller
{
    private const CACHE_TTL = 86400;

    public function configureActions(): array
    {
        return [
            'get' => [
                'prefilters' => [
                    new HttpMethod(['GET']),
                    new Authentication(),
                ],
            ],
            'upsert' => [
                'prefilters' => [
                    new HttpMethod(['POST']),
                    new Authentication(),
                ],
            ],
        ];
    }

    /**
     * Возвращает наблюдателей фронту за просроченными сделками
     *
     * @return array
     */
    public function getAction(): array
    {
        $res = $this->get();

        $result = [];
        foreach ($res as $r)
        {
            $result[] = [
                'user',
                (int)$r['USER_ID'],
            ];
        }

        return $result;
    }

    /**
     * Возвращает наблюдателей за просроченными сделками
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function get(): array
    {
        $res = NotificationRecipientTable::getList([
            'select' => [
                'ID',
                'USER_ID'
            ],
            'cache' => [
                'ttl' => static::CACHE_TTL,
            ],
        ])->fetchAll();

        return $res;
    }

    /**
     * Добавляет / обновляет наблюдателей за просроченными сделками
     *
     * @param array $fields
     * @return Result
     * @throws ArgumentException
     * @throws SystemException
     */
    public function upsertAction(array $fields = []): Result
    {
        NotificationRecipientTable::clean();

        $data = [];
        foreach ($fields as $field)
        {
            $data[] = [
                'USER_ID' => $field['userId'],
                'UPDATED_AT' => new DateTime(),
                'CREATED_AT' => new DateTime(),
            ];
        }

        if (empty($data))
        {
            return new Result();
        }

        $res = NotificationRecipientTable::addMulti($data);

        return $res;
    }
}
