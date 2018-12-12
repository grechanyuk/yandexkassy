<?php
namespace Grechanyuk\YandexKassy\Contracts;

interface YandexKassyProducts {
    /**
     * Название товара
     * @return mixed
     */
    public function getProductName();

    /**
     * Количество товара
     * @return mixed
     */
    public function getProductQuantity();

    /**
     * Стоимость товара
     * @return mixed
     */
    public function getProductPrice();

    /**
     * Код НДС, посмотреть тут: https://kassa.yandex.ru/docs/guides/#sprawochnik-znachenij-parametrow
     * Принимает значение от 1 до 6, если false - берем из файла конфигурации
     * @return mixed
     */
    public function getProductVatCode();

    /**
     * Валюта в формате ISO-4217
     * Если false - берем из файла конфигурации
     * @return mixed
     */
    public function getCurrency();
}