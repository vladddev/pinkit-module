<?php

namespace Pinkit;

use Bitrix\Main\Web\HttpClient;

class EventHandlers
{
    public function OnOrderAdd(\Bitrix\Main\Event $event)
    {
        $this->processEvent('sale.OnOrderAdd', $event);
    }

    public function OnOrderUpdate(\Bitrix\Main\Event $event)
    {
        $this->processEvent('sale.OnOrderUpdate', $event);
    }
    public function OnProductAdd(\Bitrix\Main\Event $event)
    {
        $productData = Main::getInstance()->getProduct($event->getParameter('id'));
        $arFields['PRODUCT'] = $productData;
        $this->processEvent('catalog.OnProductAdd', $event);
    }
    public function OnProductUpdate(\Bitrix\Main\Event $event)
    {
        $productData = Main::getInstance()->getProduct($event->getParameter('id'));
        $arFields['PRODUCT'] = $productData;
        $this->processEvent('catalog.OnProductUpdate', $event);
    }
    public function onAfterTopicAdd(\Bitrix\Main\Event $event)
    {
        $this->processEvent('forum.onAfterTopicAdd', $event);
    }
    public function onAfterTopicUpdate(\Bitrix\Main\Event $event)
    {
        $this->processEvent('forum.onAfterTopicUpdate', $event);
    }
    public function onAfterTopicDelete(\Bitrix\Main\Event $event)
    {
        $this->processEvent('forum.onAfterTopicDelete', $event);
    }

    private function processEvent($eventName, \Bitrix\Main\Event $event)
    {
        global $USER;

        $module = Main::getInstance();
        $data = $module->prepareQuery(
            $eventName,
            $event->getParameter('id'),
            $event->getParameter('fields'),
            $module->getUser($USER->GetID())
        );
        $urls = $module->getAllIntegrationsUrlsByEvent($eventName);
        $this->sendRequests($urls, $data);
    }

    private function sendRequests($urls = [], $data = [])
    {
        $httpClient = new HttpClient();

        foreach ($urls as $url) {
            try {
                $httpClient->post($url, $data);
            } catch (\Throwable $th) {
                continue;
            }

        }
    }
}