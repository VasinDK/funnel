<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Dk\Vasin\Controllers\FunnelMonitor;
use Bitrix\Main\SystemException;

global $APPLICATION;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('dk.vasin')) {
    throw new SystemException(Loc::getMessage('VASIN_DK_VASIN_NOT_INSTALL'));
}

Extension::load(['vasin.overdue']);

$APPLICATION->setTitle(Loc::getMessage('VASIN_SETTINGS_CONTROL'));

$fun = new FunnelMonitor();
$funnels = $fun->getAction();
?>

<div class="ui-form ui-form-section">
    <div class="ui-form-row">
        <div class="ui-form-label">
            <div class="ui-ctl-label-text"><?=Loc::getMessage('VASIN_FUNNELS_SALES')?></div>
        </div>
        <div class="ui-form-content">
            <?foreach($funnels as $key => $funnel):?>
                <div class="ui-form-row">
                    <div class="ui-form-label" data-form-row-hidden="">
                        <label class="ui-ctl ui-ctl-checkbox">
                            <input id="<?=$funnel['dealCategoryId']?>" index="<?=$key?>" type="checkbox"
                                   class="ui-ctl-element checkbox-vasin" <?=$funnel['checked']?>>
                            <div class="ui-ctl-label-text"><?=$funnel['dealCategoryName']?></div>
                        </label>
                    </div>
                    <div class="ui-form-row-hidden">
                        <div class="ui-form-row">
                            <div class="ui-ctl ui-ctl-textbox ui-ctl-w25">
                                <input type="text" placeholder="<?=Loc::getMessage('VASIN_DEAL_DAY')?>" class="ui-ctl-element input-vasin"
                                       value=<?=$funnel['daysDelay']?>>
                            </div>
                        </div>
                    </div>
                </div>
            <?endforeach;?>
        </div>
    </div>
</div>
<br/>
<button class="ui-btn ui-btn-md ui-btn-primary ui-btn-dropdown" id="recipient-button"><?=Loc::getMessage('VASIN_RECIPIENTS')?></button>
<br/>
<br/>
<br/>
<button class="ui-btn ui-btn-md ui-btn-success" id="save"><?=Loc::getMessage('VASIN_SAVE')?></button>
<button class="ui-btn ui-btn-md ui-btn-success" id="start"><?=Loc::getMessage('VASIN_START')?></button>
