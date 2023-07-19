<?php

\CModule::AddAutoloadClasses(
    'pinkit',
    array(
        'Pinkit\EventHandlers' => 'lib/eventhandlers.php',
        'Pinkit\Main' => 'lib/main.php',
    )
);
\Bitrix\Main\Loader::includeModule('main');
\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('catalog');
\Bitrix\Main\Loader::includeModule('forum');