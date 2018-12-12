<?php

return [
    'shopId' => '',
    'secretKey' => '',
    'currency' => 'RUB',
    'confirmation_type' => 'redirect',
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