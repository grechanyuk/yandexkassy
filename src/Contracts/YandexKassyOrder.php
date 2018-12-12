<?php
namespace Grechanyuk\YandexKassy\Contracts;

interface YandexKassyOrder {

    /**
     * id заказа
     * @return mixed
     */
    public function getOrderId();

    /**
     * Сумма, которая будет отправлена в кассу
     * @return mixed
     */
    public function getTotalOrder();

    /**
     * Валюта.
     * Если false - возьмем из файла конфигурации
     * @return mixed
     */
    public function getCurrency();

    /**
     * Список товаров заказа, требуется для отправки чеков.
     * Связанная модель должна реализовывать интерфейс YandexKassyProducts
     * @return mixed
     */
    public function getProducts();

    /**
     * E-mail клиента, необходим, если включена отправка чеков.
     * Можно передавать что-то одно, телефон или почту.
     * E-mail должен быть валиден!
     * @return mixed
     */
    public function getClientEmail();

    /**
     * Телефон клиента, необходим, если включена отправка чеков.
     * Можно передавать что-то одно, телефон или почту
     * @return mixed
     */
    public function getClientTelephone();
}