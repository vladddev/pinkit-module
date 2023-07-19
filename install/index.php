<?php

use Pinkit\EventHandlers;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\SystemException;


Loc::loadMessages(__FILE__);

if (class_exists('pinkit'))
{
    return;
}

class pinkit extends CModule
{
    public $MODULE_ID = 'pinkit';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME = 'Интеграция с Pinkit';
    public $MODULE_DESCRIPTION = 'Модуль интеграции Вашего cайта с системой Pinkit';
    public $MODULE_GROUP_RIGHTS = 'N';
    protected bool $errors = false;

    public const ALLOWED_EVENTS = [
        [
            'module' => 'sale',
            'event' => 'OnOrderAdd' // Создание заказа
        ],
        [
            'module' => 'sale',
            'event' => 'OnOrderUpdate' // Обновление заказа
        ],
        [
            'module' => 'catalog',
            'event' => 'OnProductAdd' // Создание товара
        ],
        [
            'module' => 'catalog',
            'event' => 'OnProductUpdate' // Обновление товара
        ],
        [
            'module' => 'forum',
            'event' => 'onAfterTopicAdd' // Создание темы на форуме
        ],
        [
            'module' => 'forum',
            'event' => 'onAfterTopicUpdate' // Обновление темы на форуме
        ],
        [
            'module' => 'forum',
            'event' => 'onAfterTopicDelete' // Удаление темы на форуме
        ],
    ];

    public function __construct()
    {
        $arModuleVersion = [];
        include('version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->PARTNER_NAME = 'Pinkit';
        $this->PARTNER_URI = 'https://pinkit.io/';
    }

    public function DoInstall(): bool
    {
        RegisterModule($this->MODULE_ID);
        $this->InstallFiles();
        $this->InstallDB();
        $eventManager = EventManager::getInstance();

        foreach (self::ALLOWED_EVENTS as $allowedEvent) {
            $eventManager->registerEventHandler($allowedEvent['module'], $allowedEvent['event'], $this->MODULE_ID, 'Pinkit\EventHandlers', $allowedEvent['event']);
        }

        return true;
    }

    /** Создает таблицы модуля
     * @return bool
     * @throws LoaderException
     */
    public function InstallDB(): bool
    {
        global $DB, $APPLICATION;
        if (Loader::includeModule($this->MODULE_ID))
        {

        }
        return true;
    }

    public function DoUninstall(): bool
    {
        $this->UnInstallDB();
        $this->UnInstallFiles();
        $eventManager = EventManager::getInstance();

        foreach (self::ALLOWED_EVENTS as $allowedEvent) {
            $eventManager->unRegisterEventHandler($allowedEvent['module'], $allowedEvent['event'], $this->MODULE_ID, 'Pinkit\EventHandlers', $allowedEvent['event']);
        }

        UnRegisterModule($this->MODULE_ID);
        return true;
    }

    /** Удаляет таблицы модуля
     * @param array $arParams
     */
    public function UnInstallDB(array $arParams = [])
    {

    }

    public function InstallFiles($arParams = []): bool
    {
        return true;
    }

    public function UnInstallFiles(array $arParams = []): bool
    {
        return true;
    }

    /**
     * Создает таблицу, если она не существует
     * @param string $tableClassName Класс сущности таблицы
     * @param string $connection Название соединения с базой данных
     * @throws SystemException
     */
    public function createTableIfNotExists(string $tableClassName, string $connection = ''): void
    {
        $connection = Application::getInstance()->getConnection($connection);

        $tableName = $tableClassName::getTableName();
        if (!$connection->isTableExists($tableName))
        {
            $tableClassName::getEntity()->createDbTable();
        }
    }

    /**
     * Удаляет таблицу, если она существует
     * @param string $tableClassName
     * @param string $connectionName
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function dropTableIfExists(string $tableClassName, string $connectionName = ''): void
    {
        $tableName = $tableClassName::getTableName();
        $connection = Application::getInstance()->getConnection($connectionName);
        if ($connection->isTableExists($tableName))
        {
            $connection->dropTable($tableName);
        }
    }
}
