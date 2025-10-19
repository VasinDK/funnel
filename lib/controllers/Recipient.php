<?php

declare(strict_types=1);

namespace Dk\Vasin\Controllers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Dk\Vasin\Services\RecipientService;

class Recipient extends Controller
{
    private RecipientService $recipientService;

    public function __construct()
    {
        $this->recipientService = new RecipientService();
        parent::__construct();
    }

    /**
     * @return array[]
     */
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
     * Возвращает фронту наблюдателей за просроченными сделками
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAction(): array
    {
        try {
            $recipients = $this->recipientService->getRecipients();

            $res = [];
            foreach ($recipients as $recipient) {
                $res[] = [
                    'user',
                    (int)$recipient['USER_ID'],
                ];
            }

            return $res;

        } catch (SystemException $e) {
            $this->addError($e->getError());
        }

        return [];
    }


    /**
     * Добавляет / обновляет наблюдателей за просроченными сделками
     *
     * @param array $items
     * @return array
     * @throws ArgumentException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function upsertAction(array $items = []): array
    {
        try {
            $res = $this->recipientService->upsert($items);

            if (!$res->isSuccess()) {
                $this->addError($res->getError());
            }

        } catch (SystemException $e) {
            $this->addError($e->getError());
        }

        return [];
    }
}
