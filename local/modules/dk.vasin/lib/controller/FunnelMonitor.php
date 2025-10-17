<?php

declare(strict_types=1);

namespace Dk\Vasin\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Config\Option;
use Dk\Vasin\Models\FunnelMonitorTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FunnelMonitor extends Controller
{
    private array $recipients;
    private int $systemUser = 0;
    private string $scheme;
    private string $host;
    private const CACHE_TTL = 3600;
    public function __construct()
    {
        parent::__construct();

        if (!Loader::includeModule('crm')) {
            throw new SystemException(Loc::getMessage('VASIN_MOD_CRM_NOT_INSTALL'));
        }

        if (!Loader::includeModule('im')) {
            throw new SystemException(Loc::getMessage('VASIN_MOD_IM_NOT_INSTALL'));
        }

        $server = Context::getCurrent()->getServer();
        $this->scheme = $server->getRequestScheme();
        $this->host = $server->getHttpHost();
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
     */
    public function getAction(): array
    {
        $funnels = DealCategory::getAll(true);
        $selectedArr = $this->getSelectedFunnels();

        $result = [];
        foreach($funnels as $value)
        {
            if ($value['IS_LOCKED'] == 'Y') {
                continue;
            }

            $daysDelay = 0;
            $checked = '';
            if ($selectedArr[$value['ID']]) {
                $daysDelay = (int)$selectedArr[$value['ID']]['DAYS_DELAY'];
                $checked = $selectedArr[$value['ID']]['CHECKED'];
            }

            $result[] = [
                'dealCategoryId' => (int)$value['ID'],
                'dealCategoryName' => $value['NAME'],
                'daysDelay' => $daysDelay,
                'checked'   => $checked,
            ];
        }

        return $result;
    }

    /**
     * Обновляет / добавляет воронки продаж
     *
     * @param array $fields
     * @return Result
     * @throws ArgumentException
     * @throws SystemException
     */
    public function upsertAction(array $fields = []): Result
    {
        FunnelMonitorTable::clean();

        $data = [];
        foreach ($fields as $field)
        {
            $data[] = [
                'DEAL_CATEGORY_ID' => (int)$field['dealCategoryId'],
                'DAYS_DELAY' => (int)$field['daysDelay'],
                'UPDATED_AT' => new DateTime(),
                'CREATED_AT' => new DateTime(),
            ];
        }

        if (empty($data))
        {
            return new Result();
        }

        $res = FunnelMonitorTable::addMulti($data);

        return $res;
    }

    /**
     * Проверяет воронки на наличие просроченных сделок
     *
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    public function checkAction(): array
    {
        $selectedFunnelArr = $this->getSelectedFunnels();

        $deals = DealTable::getList([
            'select' => ['ID', 'TITLE', 'CLOSEDATE', 'CLOSED', 'CATEGORY_ID'],
            'filter' => [
                '=CATEGORY_ID' => array_keys($selectedFunnelArr),
                '=CLOSED' => 'N',
                '>CLOSEDATE' => new DateTime(),
            ],
        ])->fetchAll();

        $this->recipients = Recipient::get();
        $currentDate = new DateTime();

        foreach($deals as $deal)
        {
            $daysDelay = (int)$selectedFunnelArr[$deal['CATEGORY_ID']]['DAYS_DELAY'];
            $dateExcess = $deal['CLOSEDATE']->add("+$daysDelay day");

            if ($currentDate->getTimestamp() > $dateExcess->getTimestamp())
            {
                $this->sendMessage($deal);
            }
        }

        return [];
    }

    /**
     * Получает интересующие воронки
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getSelectedFunnels(): array
    {
        $selected = FunnelMonitorTable::getList([
            'select' => [
                'ID',
                'DEAL_CATEGORY_ID',
                'DAYS_DELAY',
            ],
            'cache' => [
                'ttl' => static::CACHE_TTL,
            ],
        ])->fetchAll();

        $selectedArr = [];
        foreach ($selected as $s)
        {
            $selectedArr[$s['DEAL_CATEGORY_ID']] = [
                'DAYS_DELAY' => (int)$s['DAYS_DELAY'],
                'CHECKED'   => 'checked',
            ];
        }

        return $selectedArr;
    }

    /**
     * Рассылает сообщение заинтересованным лицам
     * с указанием просроченных сделок
     *
     * @param array $deal
     * @return void
     */
    private function sendMessage(array $deal): void
    {
        $dealAddress = Option::get('crm', 'path_to_deal_details');
        $dealUrl = str_replace('#deal_id#', $deal['ID'], $dealAddress);
        $url = "{$this->scheme}://{$this->host}{$dealUrl}";
        $title = $deal['TITLE'];

        foreach ($this->recipients as $recipient)
        {
            $arMessageFields = [
                "TO_USER_ID" => $recipient['USER_ID'],
                "FROM_USER_ID" => $this->systemUser,
                "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
                "NOTIFY_MODULE" => "dk_vasin",
                "NOTIFY_TAG" => "FUNNEL_MONITOR" . $recipient['USER_ID'],
                "NOTIFY_MESSAGE" => Loc::getMessage('VASIN_ATTENTION_OVERDUE_TASK') . $title . " <br/> " . $url,
            ];

            \CIMNotify::Add($arMessageFields);
        }
    }
}
