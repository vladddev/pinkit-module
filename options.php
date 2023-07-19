<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();


use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;


global $USER, $APPLICATION;
Loc::loadMessages(__FILE__);
Loader::includeModule('pinkit');

$pinkit = new \Pinkit\Main();

if (!$USER->IsAdmin())
{
    $APPLICATION->AuthForm();
}

$tabs = [
    [
        'DIV' => $pinkit::MODULE_NAME,
        'TAB' => 'Настройки интеграции',
        'TITLE' => 'Настройки интеграции'
    ],
];
$tabControl = new CAdminTabControl('tabControl', $tabs);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$pinkit->trySaveOptions($request);
$savedWebhooks = $pinkit->getSavedOptionsArray();

?>
<script type="text/javascript">
    function settingsAddRow(el)
    {
        var row = BX.findParent(el, { 'tag': 'tr'});
        var tbl = row.parentNode;

        var tableRow = tbl.rows[row.rowIndex-1].cloneNode(true);
        tbl.insertBefore(tableRow, row);

        var event = BX.findChild(tableRow.cells[1], { 'tag': 'select'}, true);
        event.selectedIndex = 0;

        var url = BX.findChild(tableRow.cells[0], { 'tag': 'input'}, true);
        url.value = '';
    }

	function settingsDeleteRow(el)
    {
        BX.remove(BX.findParent(el, {'tag': 'tr'}));
        return false;
    }
</script>
<form method="post" action="<?php $APPLICATION->GetCurPage() ?>?lang=<?= LANG ?>&mid=<?= $pinkit::MODULE_NAME ?>">
    <?php
echo bitrix_sessid_post();
$tabControl->Begin();

foreach ($tabs as $tab)
{
    $tabControl->BeginNextTab();

    foreach ($savedWebhooks as $savedWebhook) { ?>
        <tr>
            <td width="50%">
                <label>
                    Ссылка:
                    <?php echo $pinkit->buildUrlInput($savedWebhook['url']) ?>
                </label>
            </td>
            <td width="20%">
                <label>
                    Событие:
                    <?php echo $pinkit->buildEventsSelect($savedWebhook['event']) ?>
                </label>
            </td>
            <td width="30%">
                <a href="javascript:void(0)" onclick="settingsDeleteRow(this)" hidefocus="true" class="adm-btn">Удалить строку</a>
            </td>
        </tr>
    <?php }
?>

    <tr>
        <td style="padding-bottom:10px;">
            <a href="javascript:void(0)" onclick="settingsAddRow(this)" hidefocus="true" class="adm-btn">Добавить строку</a>
        </td>
        <td></td>
        <td></td>
    </tr>

<?php

    $tabControl->EndTab();
}

$tabControl->Buttons([
    'btnApply' => true
]);


$tabControl->End();
?>
</form>
