<?php

namespace Grechanyuk\YandexKassy;

use Grechanyuk\YandexKassy\YandexCheckout\Client;
use Grechanyuk\YandexKassy\YandexCheckout\Helpers\UUID;
use Grechanyuk\YandexKassy\Models\YandexKassy as MYandexKassy;

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
     * @return YandexKassy|string $payment
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ApiException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\BadApiRequestException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ForbiddenException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\InternalServerError
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\NotFoundException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ResponseProcessingException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\TooManyRequestsException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\UnauthorizedException
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
            if(config('yandexkassy.confirmation_type') == 'redirect') {
                $confirmation = [
                    'type' => config('yandexkassy.confirmation_type'),
                    'return_url' => config('yandexkassy.return_url')
                ];
            } else {
                $confirmation = [
                    'type' => config('yandexkassy.confirmation_type')
                ];
            }
        }

        array_add($paymentArray, 'confirmation', $confirmation);

        if($description) {
            array_add($paymentArray, 'description', $description);
        }

        $idempotenceKey = UUID::v4();

        $response = $this->client->createPayment($paymentArray, $idempotenceKey);
        $response = json_decode($response);

        $payment = MYandexKassy::create([
            'payment_id' => $response->id,
            'order_id' => $order_id,
            'amount' => $amount['value'],
            'idempotenceKey' => $idempotenceKey
        ]);

        return $response->confirmation->type == 'redirect' ? $response->confirmation->confirmation_url : $payment;
    }

    /**
     * @param string $paymentId
     * @param array|float $amount
     * @param string $idempotenceKey
     * @return YandexKassy $payment
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ApiException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\BadApiRequestException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ForbiddenException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\InternalServerError
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\NotFoundException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ResponseProcessingException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\TooManyRequestsException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\UnauthorizedException
     */
    public function confirmPayment($paymentId, $amount, $idempotenceKey) {
        if(!is_array($amount)) {
            $amount = [
                'value' => $amount,
                'currency' => config('yandexkassy.currency')
            ];
        }

        $response = $this->client->capturePayment($amount, $paymentId, $idempotenceKey);
        $payment = MYandexKassy::where('payment_id', $paymentId)->first();

        $payment->update([
            'status' => $response->status
        ]);

        return $payment;
    }

    /**
     * @param string $paymentId
     * @param string $idempotenceKey
     * @return YandexKassy $payment
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ApiException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\BadApiRequestException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ForbiddenException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\InternalServerError
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\NotFoundException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\ResponseProcessingException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\TooManyRequestsException
     * @throws \Grechanyuk\YandexKassy\YandexCheckout\Common\Exceptions\UnauthorizedException
     */
    public function cancelPayment($paymentId, $idempotenceKey) {
        $this->client->cancelPayment($paymentId, $idempotenceKey);

        $payment = MYandexKassy::where('payment_id', $paymentId)->first();

        $payment->update([
            'status' => ''
        ]);

        return $payment;
    }
}