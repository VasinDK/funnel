<?php

declare(strict_types=1);

namespace Dk\Vasin\Services;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Data\AddResult;
use Dk\Vasin\Helpers\Logger;
use Dk\Vasin\Models\FunnelMonitorTable;

Loc::loadMessages(__FILE__);

class FunnelService
{
    private int $systemUser = 0;
    private string $scheme;
    private string $host;
    private array $recipients;
    private const CACHE_TTL = 3600;

    public function __construct()
    {
        $server = Context::getCurrent()->getServer();
        $this->scheme = $server->getRequestScheme();
        $this->host = $server->getHttpHost();
    }

    /**
     * Сервис возвращает список воронок продаж
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getFunnels(): array
    {
        $funnels = DealCategory::getAll(true);
        if (empty($funnels)) {
            return [];
        }

        $result = [];
        $selectedFunnels = $this->getSelectedFunnels();

        foreach($funnels as $value) {
            if ($value['IS_LOCKED'] == 'Y') {
                continue;
            }

            $daysDelay = 0;
            $checked = '';
            if ($selectedFunnels[$value['ID']]) {
                $daysDelay = (int)$selectedFunnels[$value['ID']]['DAYS_DELAY'];
                $checked = 'checked';
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
     * Сервис обновляет / добавляет воронки продаж
     *
     * @param array $items
     * @return AddResult
     * @throws ArgumentException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function upsert(array $items): AddResult
    {
        FunnelMonitorTable::clean();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'DEAL_CATEGORY_ID' => (int)$item['dealCategoryId'],
                'DAYS_DELAY' => (int)$item['daysDelay'],
                'UPDATED_AT' => new DateTime(),
                'CREATED_AT' => new DateTime(),
            ];
        }

        if (empty($data)) {
            return new AddResult();
        }

        return FunnelMonitorTable::addMulti($data);
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

        $selectedFunnels = [];
        foreach ($selected as $s) {
            $selectedFunnels[$s['DEAL_CATEGORY_ID']] = [
                'DAYS_DELAY' => (int)$s['DAYS_DELAY'],
            ];
        }

        return $selectedFunnels;
    }

    /**
     * Проверяет воронки на наличие просроченных сделок и отправка уведомлений
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function check(): array
    {
        $selectedFunnels = $this->getSelectedFunnels();
        if(empty($this->getRecipients())) {
            return [];
        }

        $deals = DealTable::getList([
            'select' => ['ID', 'TITLE', 'CLOSEDATE', 'CLOSED', 'CATEGORY_ID'],
            'filter' => [
                '=CATEGORY_ID' => array_keys($selectedFunnels),
                '=CLOSED' => 'N',
                '>CLOSEDATE' => new DateTime(),
            ],
        ])->fetchAll();

        $currentDate = new DateTime();
        foreach($deals as $deal) {
            $daysDelay = (int)$selectedFunnels[$deal['CATEGORY_ID']]['DAYS_DELAY'];
            $dateExcess = $deal['CLOSEDATE']->add("+$daysDelay day");

            if ($currentDate->getTimestamp() > $dateExcess->getTimestamp()) {
                $this->sendMessage($deal);
            }
        }

        Logger::add(Loc::getMessage('VASIN_DEADLINE_VERIFICATION_COMPLETED'));

        return [];
    }

    /**
     * Рассылает сообщение заинтересованным лицам с указанием просроченных сделок
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

        foreach ($this->recipients as $recipient) {
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

    /**
     * Сервис получения заинтересованных лиц
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getRecipients(): array
    {
        if (empty($this->recipients)) {
            $recipientService = new RecipientService();
            $this->recipients = $recipientService->getRecipients();
        }

        return $this->recipients;
    }
}
