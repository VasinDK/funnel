<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Context;
use Dk\Vasin\Models\NotificationRecipientTable;
use Dk\Vasin\Models\FunnelMonitorTable;
use Dk\Vasin\Models\LoggerTable;
use Dk\Vasin\Helpers\Logger;

Loc::loadMessages(__FILE__);

class dk_vasin extends CModule
{
    public $MODULE_ID = 'dk.vasin';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        include __dir__ . '/version.php';

        if (isset($arModuleVersion['VERSION'], $arModuleVersion['VERSION_DATE'])) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('VASIN_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VASIN_MODULE_DESCRIPTION');
    }

    public function DoInstall(): bool
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallFiles();
        $this->InstallDB();

        Logger::add(Loc::getMessage('VASIN_MODULE_INSTALLED'));

        return true;
    }

    public function DoUninstall(): void
    {
        global $APPLICATION;

        $context = Context::getCurrent()->getRequest();

        if ($context->getQuery('step') != '2') {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('VASIN_MODULE_UNINSTALL'),
                Application::getDocumentRoot() . '/local/modules/dk.vasin/install/unstep1.php'
            );
        }
        elseif ($context->getQuery('step') === '2') {
            $this->UnInstallFiles();

            if ($context->getQuery('table') === 'Y') {
                $this->UnInstallDB();
            }

            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
    }

    public function InstallFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            Application::getDocumentRoot() . '/bitrix/admin',
            true,
            true
        );
    }

    public function UnInstallFiles(): void
    {
        Directory::deleteDirectory(Application::getDocumentRoot() . '/bitrix/admin/vasin-menu.php');
        Directory::deleteDirectory(Application::getDocumentRoot() . '/bitrix/admin/vasin-overdue.php');
    }

    public function InstallDB(): void
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            LoggerTable::getEntity()->createDbTable();
            FunnelMonitorTable::getEntity()->createDbTable();
            NotificationRecipientTable::getEntity()->createDbTable();
        }
    }

    public function UnInstallDB(): void
    {
        if (!Loader::includeModule($this->MODULE_ID)) {
            return;
        }

        $connection = Application::getInstance()->getConnection();

        if ($connection->isTableExists(LoggerTable::getTableName()))
        {
            $connection->dropTable(LoggerTable::getTableName());
            $connection->dropTable(FunnelMonitorTable::getTableName());
            $connection->dropTable(NotificationRecipientTable::getTableName());
        }
    }
}