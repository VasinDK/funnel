<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('dk.vasin', [
   Dk\Vasin\Models\NotificationRecipientTable::class    => 'lib/models/NotificationRecipientTable.php',
   Dk\Vasin\Models\FunnelMonitorTable::class            => 'lib/models/FunnelMonitorTable.php',
   Dk\Vasin\Models\LoggerTable::class                   => 'lib/models/LoggerTable.php',
   Dk\Vasin\Helpers\Logger::class                       => 'lib/helpers/Logger.php',
]);
