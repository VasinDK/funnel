<?php

declare(strict_types=1);

namespace Dk\Vasin\Controllers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\ObjectPropertyException;
use Dk\Vasin\Services\FunnelService;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FunnelMonitorController extends Controller
{
    private FunnelService $funnelService;

    public function __construct()
    {
        parent::__construct();

        if (!Loader::includeModule('crm')) {
            throw new SystemException(Loc::getMessage('VASIN_MOD_CRM_NOT_INSTALL'));
        }

        if (!Loader::includeModule('im')) {
            throw new SystemException(Loc::getMessage('VASIN_MOD_IM_NOT_INSTALL'));
        }

        $this->funnelService = new FunnelService();
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
            'check' => [
                'prefilters' => [
                    new HttpMethod(['POST']),
                    new Authentication(),
                ],
            ],
        ];
    }

    /**
     * Возвращает список воронок продаж
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAction(): array
    {
        try {
            return $this->funnelService->getFunnels();

        } catch (SystemException $e) {
            $this->addError($e->getError());
        }

        return [];
    }

    /**
     * Обновляет / добавляет воронки продаж
     *
     * @param array $items
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws SqlQueryException
     */
    public function upsertAction(array $items = []): array
    {
        try {
            $this->funnelService->upsert($items);

        } catch (SystemException $e) {
            $this->addError($e->getError());
        }

        return [];
    }

    /**
     * Проверяет воронки на наличие просроченных сделок
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function checkAction(): array
    {
        try {
            $this->funnelService->check();

        } catch (SystemException $e) {
            $this->addError($e->getError());
        }

        return [];
    }
}
