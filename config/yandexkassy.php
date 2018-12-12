<?php

return [
    'shopId' => '',
    'secretKey' => '',
    'currency' => 'RUB',
    'confirmation_type' => 'redirect',
    /**
     * Данный URL можно указать в настройках ЛК яндекс кассы для приема уведомлений о сменах статуса
     * Переход запустит Event с данными от кассы, его можно прослушать.
     * Как слушать события: http://laravel.su/docs/5.4/Events#registering-events-and-listeners
     * Вызываемое событие: Grechanyuk\YandexKassy\Events\YandexKassyPaymentResult
     * Так же необходимо дабвить данный URL в исключения CSRF-защиты: http://laravel.su/docs/5.4/csrf#csrf-excluding-uris
     */
    'events_url' => 'api/yandexkassy/confirm/payment',
    'return_url' => '',

    /**
     * Блок настройки чеков
     */

    'receipt' => [

        /**
         * Требуется ли отправлять чеки? По умолчанию - false
         */

        'enabled' => false,

        /**
         * Ставка НДС. Возможные значения — числа от 1 до 6.
         * Подробнее про коды ставки НДС: https://kassa.yandex.ru/docs/guides/#sprawochnik-znachenij-parametrow
         */

        'vat_code' => ''
    ],

    /**
     * Блок тестов. Работать в режиме тестового магазина? По умолчанию - false
     */

    'test_mode' => [
        'enabled' => false,
        'shopId' => '',
        'secretKey' => ''
    ]

];