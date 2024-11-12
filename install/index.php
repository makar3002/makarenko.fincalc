<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use makarenko\fincalc\reports\boundary\DataFacade;
use makarenko\fincalc\reports\boundary\DataRestFacade;
use makarenko\fincalc\reports\boundary\CalculationFacade;
use makarenko\fincalc\reports\control\ChangeDataTable;
use makarenko\fincalc\reports\control\DataHistoryTable;
use makarenko\fincalc\handler\IblockImport;

class makarenko_fincalc extends CModule
{
    const MODULE_ID = 'makarenko.fincalc';
    const SITE_ID = 's1';

    var $MODULE_ID = self::MODULE_ID;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';

    function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = Loc::getMessage('MAKARENKO_FINCALC.MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MAKARENKO_FINCALC.MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('MAKARENKO_FINCALC.PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('MAKARENKO_FINCALC.PARTNER_URI');
    }

    function DoInstall()
    {
        ModuleManager::registerModule(self::MODULE_ID);
        Loader::includeModule(self::MODULE_ID);
        Option::set(self::MODULE_ID, 'VERSION_DB', $this->versionToInt());

        $this->InstallEvents();
        $this->InstallDB();
        $this->installAgents();
    }

    function DoUninstall()
    {
        Loader::includeModule('makarenko.fincalc');
        $this->unInstallAgents();
        $this->UnInstallDB();
        $this->UnInstallEvents();

        Option::delete(self::MODULE_ID, array('name' => 'VERSION_DB'));
        ModuleManager::unRegisterModule(self::MODULE_ID);
    }

    function InstallDB()
    {
        $db = Application::getConnection();

        if (!$db->isTableExists(DataHistoryTable::getTableName())) {
            $db->query('
                    create table if not exists fincalc_data_history (
                            ID int not null auto_increment,
                            NAME varchar(255),
                            DATA_TYPE int not null,
                            PERIOD_ID int,
                            FRC_ID int not null,
                            INDEX_ID int,
                            ITEM_ID int,
                            ORIGINAL_CURRENCY int,
                            SUM_IN_ORIGINAL_CURRENCY float,
                            SUM_IN_USD float,
                            ALLOCATION_LEVEL_ID int,
                            SNAPSHOT datetime,
                            AFFILIATED_FRC_ID int,
                            primary key (ID)
                    );
            ');
        }

        if (!$db->isTableExists(ChangeDataTable::getTableName())) {
            ChangeDataTable::getEntity()->createDbTable();
        }
    }

    function UnInstallDB()
    {
        $db = Application::getConnection();
        if ($db->isTableExists(DataHistoryTable::getTableName())) {
            $db->dropTable(DataHistoryTable::getTableName());
        }
    }

    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnStartIblockElementAdd',
            self::MODULE_ID,
            IblockImport::class,
            'extendImport'
        );

        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnStartIBlockElementUpdate',
            self::MODULE_ID,
            IblockImport::class,
            'extendImport'
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnBeforeIBlockElementAdd',
                'makarenko.fincalc',
                DataFacade::class,
                'preventAddingExistsElement',
                50
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnBeforeIBlockElementUpdate',
                'makarenko.fincalc',
                DataFacade::class,
                'preventUpdatingExistsElement',
                50
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnAfterIBlockElementAdd',
                'makarenko.fincalc',
                DataFacade::class,
                'archiveChangedDataElement',
                25
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnAfterIBlockElementUpdate',
                'makarenko.fincalc',
                DataFacade::class,
                'archiveChangedDataElement',
                25
        );

        $eventManager->registerEventHandlerCompatible(
                'rest',
                'OnRestServiceBuildDescription',
                'makarenko.fincalc',
                DataRestFacade::class,
                'onRestServiceBuildDescription'
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnAfterIBlockElementAdd',
                'makarenko.fincalc',
                DataFacade::class,
                'saveDataChange'
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnAfterIBlockElementUpdate',
                'makarenko.fincalc',
                DataFacade::class,
                'saveDataChange'
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnIBlockElementDelete',
                'makarenko.fincalc',
                DataFacade::class,
                'rememberDataChangeBeforeDelete'
        );

        $eventManager->registerEventHandlerCompatible(
                'iblock',
                'OnAfterIBlockElementDelete',
                'makarenko.fincalc',
                DataFacade::class,
                'saveDataChange'
        );
    }

    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnStartIblockElementAdd',
            self::MODULE_ID,
            IblockImport::class,
            'extendImport'
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnStartIBlockElementUpdate',
            self::MODULE_ID,
            IblockImport::class,
            'extendImport'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnBeforeIBlockElementAdd',
                'makarenko.fincalc',
                DataFacade::class,
                'preventAddingExistsElement'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnBeforeIBlockElementUpdate',
                'makarenko.fincalc',
                DataFacade::class,
                'preventUpdatingExistsElement'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementAdd',
                'makarenko.fincalc',
                DataFacade::class,
                'archiveChangedDataElement'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementUpdate',
                'makarenko.fincalc',
                DataFacade::class,
                'archiveChangedDataElement'
        );

        $eventManager->unRegisterEventHandler(
                'rest',
                'OnRestServiceBuildDescription',
                'makarenko.fincalc',
                DataRestFacade::class,
                'onRestServiceBuildDescription'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementAdd',
                'makarenko.fincalc',
                DataFacade::class,
                'saveDataChange'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementUpdate',
                'makarenko.fincalc',
                DataFacade::class,
                'saveDataChange'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnIBlockElementDelete',
                'makarenko.fincalc',
                DataFacade::class,
                'rememberDataChangeBeforeDelete'
        );

        $eventManager->unRegisterEventHandler(
                'iblock',
                'OnAfterIBlockElementDelete',
                'makarenko.fincalc',
                DataFacade::class,
                'saveDataChange'
        );
    }

    public function InstallFiles()
    {
        $root = Application::getDocumentRoot();

        CopyDirFiles(
            __DIR__.'/files/components/makarenko.fincalc/',
            $root.'/local/components/makarenko.fincalc/',
            true,
            true
        );
        CopyDirFiles(
            __DIR__.'/files/js/',
            $root.'/local/js/'.self::MODULE_ID,
            true,
            true
        );
        CopyDirFiles(
            __DIR__.'/files/images/',
            $root.'/local/images/'.self::MODULE_ID,
            true,
            true
        );
        CopyDirFiles(__DIR__ . '/files/public', $root, true, true);
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx('local/js/makarenko.fincalc/');
        DeleteDirFilesEx('local/components/makarenko.fincalc/');
        DeleteDirFilesEx('local/images/makarenko.fincalc/');
    }

    public function installAgents()
    {
        CalculationFacade::createCalculationAgent();
        CalculationFacade::createIterativeCalculationAgent();
    }

    public function unInstallAgents()
    {
        \CAgent::RemoveModuleAgents(self::MODULE_ID);
    }

    private function versionToInt()
    {
        return intval(preg_replace('/[^0-9]+/i', '', $this->MODULE_VERSION_DATE));
    }
}
