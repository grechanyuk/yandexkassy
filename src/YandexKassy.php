<?php

namespace Grechanyuk\YandexKassy;

use YandexCheckout\Client;
use YandexCheckout\Helpers\UUID;

class YandexKassy
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuth(config('yandexkassy.shopId'), config('yandexkassy.secretKey'));
    }

    /**
     * @param int $order_id
     * @param array|float $amount
     * @param string|bool $description
     * @param bool $capture
     * @param array|bool $payment_method_data
     * @param array|bool $confirmation
     * @return YandexKassy $payment
     * @throws \YandexCheckout\Common\Exceptions\ApiException
     * @throws \YandexCheckout\Common\Exceptions\BadApiRequestException
     * @throws \YandexCheckout\Common\Exceptions\ForbiddenException
     * @throws \YandexCheckout\Common\Exceptions\InternalServerError
     * @throws \YandexCheckout\Common\Exceptions\NotFoundException
     * @throws \YandexCheckout\Common\Exceptions\ResponseProcessingException
     * @throws \YandexCheckout\Common\Exceptions\TooManyRequestsException
     * @throws \YandexCheckout\Common\Exceptions\UnauthorizedException
     */

    public function newPayment($order_id, $amount, $description = false, $capture = false, $payment_method_data = false, $confirmation = false)
    {
        if(!is_array($amount)) {
            $amount = [
                'value' => $amount,
                'currency' => config('yandexkassy.currency')
            ];
        }

        $paymentArray = [
            'amount' => $amount,
            'capture' => $capture,
            'client_ip' => \Request::ip(),
            'metadata' => [
                'order_id' => $order_id
            ]
        ];

        if($payment_method_data) {
            array_add($paymentArray, 'payment_method_data', $payment_method_data);
        }

        if(!$confirmation) {
            $confirmation = [
                'type' => config('yandexkassy.confirmation_type'),
                'return_url' => config('yandexkassy.return_url')
            ];
        }

        array_add($paymentArray, 'confirmation', $confirmation);

        if($description) {
            array_add($paymentArray, 'description', $description);
        }

        $idempotenceKey = UUID::v4();

        $response = $this->client->createPayment($paymentArray, $idempotenceKey);

        $payment = YandexKassy::create([
            'payment_id' => '',
            'order_id' => $order_id,
            'amount' => $amount['value'],
            'status' => '',
            'idempotenceKey' => $idempotenceKey
        ]);

        return $payment;
    }

    /**
     * @param string $paymentId
     * @param array|float $amount
     * @param string $idempotenceKey
     * @return YandexKassy $payment
     * @throws \YandexCheckout\Common\Exceptions\ApiException
     * @throws \YandexCheckout\Common\Exceptions\BadApiRequestException
     * @throws \YandexCheckout\Common\Exceptions\ForbiddenException
     * @throws \YandexCheckout\Common\Exceptions\InternalServerError
     * @throws \YandexCheckout\Common\Exceptions\NotFoundException
     * @throws \YandexCheckout\Common\Exceptions\ResponseProcessingException
     * @throws \YandexCheckout\Common\Exceptions\TooManyRequestsException
     * @throws \YandexCheckout\Common\Exceptions\UnauthorizedException
     */
    public function confirmPayment($paymentId, $amount, $idempotenceKey) {
        if(!is_array($amount)) {
            $amount = [
                'value' => $amount,
                'currency' => config('yandexkassy.currency')
            ];
        }

        $response = $this->client->capturePayment($amount, $paymentId, $idempotenceKey);
        $payment = YandexKassy::where('payment_id', $paymentId)->first();

        $payment->update([
            'status' => $response->status
        ]);

        return $payment;
    }

    /**
     * @param string $paymentId
     * @param string $idempotenceKey
     * @return YandexKassy $payment
     * @throws \YandexCheckout\Common\Exceptions\ApiException
     * @throws \YandexCheckout\Common\Exceptions\BadApiRequestException
     * @throws \YandexCheckout\Common\Exceptions\ForbiddenException
     * @throws \YandexCheckout\Common\Exceptions\InternalServerError
     * @throws \YandexCheckout\Common\Exceptions\NotFoundException
     * @throws \YandexCheckout\Common\Exceptions\ResponseProcessingException
     * @throws \YandexCheckout\Common\Exceptions\TooManyRequestsException
     * @throws \YandexCheckout\Common\Exceptions\UnauthorizedException
     */
    public function cancelPayment($paymentId, $idempotenceKey) {
        $this->client->cancelPayment($paymentId, $idempotenceKey);

        $payment = YandexKassy::where('payment_id', $paymentId)->first();

        $payment->update([
            'status' => ''
        ]);

        return $payment;
    }
}