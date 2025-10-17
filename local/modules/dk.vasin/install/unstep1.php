<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Context;

$context = Context::getCurrent()->getRequest();
?>

<form action="<?=$context->getRequestedPage()?>">
    <?= bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?= LANG?>">
    <input type="hidden" name="id" value="dk.vasin">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <p>
        <input type="checkbox" name="table" id="table" value="Y" checked>
        <label for="table"><?= Loc::getMessage('VASIN_MOD_UNINST_SAVE_TABLES')?></label>
    </p>
    <input type="submit" name="inst" value="<?= Loc::getMessage('VASIN_MOD_UNINST_DEL')?>">
</form>
