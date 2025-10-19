<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('dk.vasin', [
   Dk\Vasin\Models\NotificationRecipientTable::class    => 'lib/models/NotificationRecipientTable.php',
   Dk\Vasin\Models\FunnelMonitorTable::class            => 'lib/models/FunnelMonitorTable.php',
   Dk\Vasin\Models\LoggerTable::class                   => 'lib/models/LoggerTable.php',
   Dk\Vasin\Helpers\Logger::class                       => 'lib/helpers/Logger.php',
]);

$arJsConfig = [
    'vasin.overdue' => [
        'js' => '/local/modules/dk.vasin/js/vasin/script.js',
        'lang' => '/local/modules/dk.vasin/js/lang/'.LANGUAGE_ID.'/vasin/script.php',
        'rel' => [
            'ui.buttons',
            'ui.entity-selector',
            'ui.notification',
            'ui.layout-form',
        ],
    ]
];

foreach ($arJsConfig as $ext => $arExt) {
    \CJSCore::RegisterExt($ext, $arExt);
}