<?php

defined('B_PROLOG_INCLUDED') || die;
use Bitrix\Main\Localization\Loc;

global $APPLICATION, $USER;
$module_id = 'makarenko.fincalc';
$options = array(
        'fincalc' => array(
                Loc::getMessage('FINCALC_TRIGGER_SETTINGS'),
                array(
                        'FINCALC_TRIGGER_IS_ON',
                        Loc::getMessage('FINCALC_TRIGGER_IS_ON'),
                        '',
                        array('checkbox'),
                ),
                array(
                        'FINCALC_ALLOCATION_TRIGGER_IS_ON',
                        Loc::getMessage('FINCALC_ALLOCATION_TRIGGER_IS_ON'),
                        '',
                        array('checkbox'),
                ),
                Loc::getMessage('FINCALC_AGENT_SETTINGS'),
                array(
                        'FINCALC_BP_ELEMENTS_UPDATE_INTERVAL',
                        Loc::getMessage('FINCALC_BP_ELEMENTS_UPDATE_INTERVAL'),
                        '24',
                        array('text', 50),
                ),
                array(
                        'FINCALC_BP_ELEMENTS_UPDATE_TIME',
                        Loc::getMessage('FINCALC_BP_ELEMENTS_UPDATE_TIME'),
                        '00:00',
                        array('text', 50),
                ),
                Loc::getMessage('IBLOCK_IDS_SETTINGS'),
                array(
                        'FINCALC_DATA_FOR_FINCALC_IBLOCK_ID',
                        Loc::getMessage('FINCALC_DATA_FOR_FINCALC_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_FRC_IBLOCK_ID',
                        Loc::getMessage('FINCALC_FRC_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_INDEX_CODE_NAME_IBLOCK_ID',
                        Loc::getMessage('FINCALC_INDEX_CODE_NAME_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_ITEM_IBLOCK_ID',
                        Loc::getMessage('FINCALC_ITEM_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_PERIOD_IBLOCK_ID',
                        Loc::getMessage('_PERIOD_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_EXPENCE_REQUEST_IBLOCK_ID',
                        Loc::getMessage('FINCALC_EXPENCE_REQUEST_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_CURRENCY_LIST_IBLOCK_ID',
                        Loc::getMessage('FINCALC_CURRENCY_LIST_IBLOCK_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_INDEX_EXPENSES_SECTION_ID',
                        Loc::getMessage('INDEX_EXPENSES_SECTION_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_ITEM_EXPENSES_SECTION_ID',
                        Loc::getMessage('ITEM_EXPENSES_SECTION_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_INDEX_REVENUE_SECTION_ID',
                        Loc::getMessage('INDEX_REVENUE_SECTION_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_ITEM_REVENUE_SECTION_ID',
                        Loc::getMessage('ITEM_REVENUE_SECTION_ID'),
                        '',
                        array('text', 50),
                ),
                array(
                        'FINCALC_CALCULATOR_MONITORING_MODE',
                        'Режим отладки',
                        '',
                        array('checkbox'),
                ),
                array(
                        'FINCALC_DATA_IS_ONLY_ACTUAL_DATA_MODE',
                        'Только актуальные данные',
                        '',
                        array('checkbox'),
                )
        )
);

$tabs = array(
        array(
                'DIV' => 'fincalc',
                'TAB' => Loc::getMessage('MAKARENKO_FINCALC.FINCALC'),
                'TITLE' => Loc::getMessage('MAKARENKO_FINCALC.FINCALC'),
        )
);

if ($USER->IsAdmin() && check_bitrix_sessid() && strlen($_POST['save']) > 0) {
    foreach ($options as $option) {
        __AdmSettingsSaveOptions($module_id, $option);
    }
    LocalRedirect($APPLICATION->GetCurPageParam());
}

$tabControl = new CAdminTabControl('tabControl', $tabs);
$tabControl->Begin();
?>
<form
        method="POST"
        name="makarenko_fincalc"
        action="<?php echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>"
>
    <?php $tabControl->BeginNextTab(); ?>
    <?php __AdmSettingsDrawList($module_id, $options['fincalc']); ?>
    <?php $tabControl->Buttons(array('btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false)); ?>
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->End(); ?>
</form>
