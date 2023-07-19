<?php

namespace Pinkit;

use Bitrix\Main\Context;
use Bitrix\Main\Data\Cache;
use COption;

class Main
{
    /**
     * @var Main
     */
    private static $instance;
    private $host;

    public const MODULE_NAME = 'pinkit';
    public const OPTIONS_NAME = 'integrations';

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
            $protocol = Context::getCurrent()->getRequest()->isHttps() ? 'https://' : 'http://';
            static::$instance->host = $protocol . $_SERVER['HTTP_HOST'];
        }
        return static::$instance;
    }

    public function getHost()
    {
        return $this->host;
    }

    private static function getFullCachePath(array $shards = [])
    {
        return '/Pinkit/' . get_called_class()
            . '/' . implode('/', $shards);
    }

    public function getProduct($itemId)
    {
        $cache = Cache::createInstance();
        $path = static::getFullCachePath(['product', $itemId]);

        if ($cache->initCache(3600, null, $path)) {
            $items = $cache->getVars();
        } else {
            $cache->startDataCache();
            $items = $this->getProductData($itemId);
            if ($items !== null) {
                $cache->endDataCache($items);
            } else {
                $cache->abortDataCache();
            }
        }

        return $items;
    }

    /**
     * @param array $product
     * @return string
     */
    protected function getImagePath($product)
    {
        $imgId = $product['PREVIEW_PICTURE'] ?: $product['DETAIL_PICTURE'];
        return intval($imgId) ?  \CFile::GetPath($imgId) : '';
    }

    public function getProductData($itemId)
    {
        $productParams = [];
        $productId = $this->getProductId($itemId);

        $filter = ['ID' => $productId];
        $select = ['ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'];
        $productList = \CIBlockElement::GetList([], $filter, false, false, $select);

        if ($product = $productList->GetNext()) {
            $productParams = [
                'id' => $itemId,
                'name' => $product['NAME'],
                'price' => 0,
                'currency_code' => '',
                'detail_url' => $this->getHost() . $product['DETAIL_PAGE_URL'],
                'image_url' => $this->getHost() . $this->getImagePath($product),
            ];
        }

        if (!empty($productParams)) {
            $priceList = \CPrice::GetList([], ['PRODUCT_ID' => $productId]);

            if ($price = $priceList->Fetch()) {
                $productParams['price'] = $price['PRICE'];
                $productParams['currency_code'] = $price['CURRENCY'];
            }
        }

        return $productParams;
    }

    public function getUser($userId)
    {
        $cache = Cache::createInstance();
        $path = static::getFullCachePath(['user', $userId]);

        if ($cache->initCache(3600, null, $path)) {
            $user = $cache->getVars();
        } else {
            $cache->startDataCache();
            $user = $this->getUserData($userId);
            if ($user !== null) {
                $cache->endDataCache($user);
            } else {
                $cache->abortDataCache();
            }
        }

        return $user;
    }

    public function getUserData($userId)
    {
        $userParams = [];
        $userList = \Bitrix\Main\UserTable::getList([
            'filter' => ['ID' => $userId],
            'select' => ['EMAIL', 'PERSONAL_PHONE', 'NAME', 'LAST_NAME', 'WORK_COMPANY']
        ]);
        if ($user = $userList->fetch()) {
            $userParams = [
                'email' => $user['EMAIL'],
                'phone' => $user['PERSONAL_PHONE'],
                'first_name' => $user['NAME'],
                'last_name' => $user['LAST_NAME'],
                'company' => $user['WORK_COMPANY']
            ];
        }

        return $userParams;
    }

    /** Возвращает id товара
     * @param $itemId
     * @return bool
     */
    protected function getProductId($itemId)
    {
        $item = \CCatalogProduct::GetByID($itemId);

        if (empty($item)) return false;

        //если торговое предложение, то нужно получить id товара
        $productId = $itemId;
        if ($item['TYPE'] == '4') {
            $productInfo = \CCatalogSku::GetProductInfo($itemId);
            $productId = $productInfo['ID'];
        }

        return $productId;
    }

    public function prepareQuery($method, $id, $fields, $userId)
    {
        return [
            'event' => $method,
            'datetime' => (new \DateTimeImmutable('now'))->format('c'),
            'user' => $userId,
            'id' => $id,
            'data' => $fields,
        ];
    }

    public function getAllIntegrationsUrlsByEvent($eventName)
    {
        $savedIntegrationsOption = COption::GetOptionString(self::MODULE_NAME, self::OPTIONS_NAME);
        $output = [];

        if ($savedIntegrationsOption) {
            $savedIntegrations = json_decode(COption::GetOptionString(self::MODULE_NAME, self::OPTIONS_NAME), true, 512, JSON_THROW_ON_ERROR);
            foreach ($savedIntegrations as $savedIntegration) {
                if ($savedIntegration['event'] === $eventName) {
                    $output[] = $savedIntegration['url'];
                }
            }
        }

        return array_unique($output);
    }

    public function buildEventsSelect($value = ''): string
    {
        $allowedEvents = [
            'sale.OnOrderAdd',
            'sale.OnOrderUpdate',
            'catalog.OnProductAdd',
            'catalog.OnProductUpdate',
            'forum.onAfterTopicAdd',
            'forum.onAfterTopicUpdate',
            'forum.onAfterTopicDelete',
        ];

        $output = '<select class="typeselect" name="EVENT[]">';
        foreach ($allowedEvents as $eventName) {
            $output .= sprintf('<option value="%s"' . ($value === $eventName ? ' selected ' : '') . '>%s</option>', $eventName, $eventName);
        }
        $output .= '</select>';

        return $output;
    }

    public function buildUrlInput($value = ''): string
    {
        return sprintf('<input type="text" size="50" maxlength="255" value="%s" name="URL[]"/>', htmlspecialcharsbx($value));
    }

    /**
     * @throws \JsonException
     */
    public function trySaveOptions($request): void
    {
        $save = $request->get('save') || $request->get('apply');
        if (!empty($save) && $request->isPost() && check_bitrix_sessid())
        {
            $urls = $request->get('URL');
            $events = $request->get('EVENT');
            $result = [];

            for ($i=0, $iMax = count($urls); $i< $iMax; $i++) {
                if ($urls[$i] !== '' && $events[$i] !== '') {
                    $result[] = [
                        'url' => trim($urls[$i]),
                        'event' => trim($events[$i])
                    ];
                }
            }

            COption::SetOptionString(self::MODULE_NAME, self::OPTIONS_NAME, json_encode($result, JSON_THROW_ON_ERROR));

            \CAdminMessage::ShowMessage([
                'TYPE' => 'OK',
                'MESSAGE' => 'Настройки успешно сохранены',
            ]);
        }
    }

    private function getDefaultOptionsArray(): array
    {
        return [
            [
                'url' => '',
                'event' => ''
            ]
        ];
    }

    private function getSavedOptionsJson(): string
    {
        return COption::GetOptionString(self::MODULE_NAME, self::OPTIONS_NAME) ?: '[]';
    }

    /**
     * @throws \JsonException
     */
    public function getSavedOptionsArray(): array
    {
        return json_decode($this->getSavedOptionsJson(), true, 512, JSON_THROW_ON_ERROR) + $this->getDefaultOptionsArray();
    }
}