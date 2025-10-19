<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return [
    [
        'sort'        => 1,
        'section'     => 'vasin',
        'parent_menu' => 'global_menu_services',
        'module_id'   => Loc::getMessage('VASIN_MODULE_ID'),
        'items_id'    => 'menu_' . Loc::getMessage('VASIN_MODULE_ID'),
        'icon'        => 'statistic_icon_searchers',
        'page_icon'   => 'statistic_icon_searchers',
        'text'        => Loc::getMessage('VASIN_MODULE_NAME'),
        'items'       => [
            [
                'sort'      => 1,
                'text'      => Loc::getMessage('VASIN_MENU_OVERDUE'),
                'url'       => 'vasin-overdue.php?lang=' . LANG
            ],
        ]
    ]
];