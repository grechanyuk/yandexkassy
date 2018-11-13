<?php

namespace Tests\YandexCheckout\Model\PaymentData;

use YandexCheckout\Model\PaymentData\PaymentDataCash;
use YandexCheckout\Model\PaymentMethodType;

require_once __DIR__ . '/AbstractPaymentDataPhoneTest.php';

class PaymentDataCashTest extends AbstractPaymentDataPhoneTest
{
    /**
     * @return PaymentDataCash
     */
    protected function getTestInstance()
    {
        return new PaymentDataCash();
    }

    /**
     * @return string
     */
    protected function getExpectedType()
    {
        return PaymentMethodType::CASH;
    }

    public function validPhoneDataProvider()
    {
        return array(
            array('0123'),
            array('45678'),
            array('901234'),
            array('5678901'),
            array('23456789'),
            array('012345678'),
            array('9012345678'),
            array('90123456789'),
            array('012345678901'),
            array('5678901234567'),
            array('89012345678901'),
            array('234567890123456'),
            array(null),
            array(''),
        );
    }

    public function invalidPhoneDataProvider()
    {
        return array(
            array(true),
            array(false),
            array(array()),
            array(new \stdClass()),
            array('2345678901234567'),
        );
    }
}