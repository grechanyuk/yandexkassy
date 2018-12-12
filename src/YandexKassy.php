<?php

namespace Grechanyuk\YandexKassy;

use Grechanyuk\YandexKassy\Contracts\YandexKassyOrder;
use Grechanyuk\YandexKassy\Contracts\YandexKassyProducts;
use Grechanyuk\YandexKassy\Models\YandexKassy as MYandexKassy;
use YandexCheckout\Client;
use YandexCheckout\Common\Exceptions\ApiException;
use YandexCheckout\Common\Exceptions\BadApiRequestException;
use YandexCheckout\Common\Exceptions\ForbiddenException;
use YandexCheckout\Common\Exceptions\InternalServerError;
use YandexCheckout\Common\Exceptions\NotFoundException;
use YandexCheckout\Common\Exceptions\ResponseProcessingException;
use YandexCheckout\Common\Exceptions\TooManyRequestsException;
use YandexCheckout\Common\Exceptions\UnauthorizedException;
use YandexCheckout\Helpers\UUID;

class YandexKassy
{
    private $client;

    /**
     * YandexKassy constructor.
     */
    public function __construct()
    {
        $this->client = new Client();

        if(config('yandexkassy.test_mode.enabled')) {
            $shopId = config('yandexkassy.test_mode.shopId');
            $secretKey = config('yandexkassy.test_mode.secretKey');
        } else {
            $shopId = config('yandexkassy.shopId');
            $secretKey = config('yandexkassy.secretKey');
        }

        $this->client->setAuth($shopId, $secretKey);
    }


    /**
     * Создает платеж в кассу, массив $order должен содержать сумму, которую возьмем
     * с пользователя $order['total'] и валюту в формате ISO-4217 $order['currency'], если валюта false -
     * возьмем из файла конфигурации.
     * $order['products'] = ['description', 'quantity', 'amount' => ['value', 'currency'], 'vat_code'] - Список товаров
     * для отправки чека. Подробнее: https://kassa.yandex.ru/docs/checkout-api/#sozdanie-platezha
     * $order['email'] - E-mail клиента, необходим для отправки чека. Можно передать что-то одно или почту или телефон
     * $order['telephone'] - Телефон клиента, необходим для отправки чека. Можно передать что-то одно или почту или телефон
     * Если чек отключен - передавать $order['products'], $order['email'], $order['telephone'] не обязательно
     * @param array|YandexKassyOrder $order
     * @param bool $description
     * @param bool $capture
     * @param bool $payment_method_data
     * @param bool $confirmation
     * @return string|\YandexCheckout\Request\Payments\CreatePaymentResponse
     * @throws \Exception
     */
    public function createPayment($order, $description = false, $capture = false, $payment_method_data = false, $confirmation = false)
    {
        if ($order instanceof YandexKassyOrder) {
            $amount = [
                'value' => $order->getTotalOrder(),
                'currency' => $order->getCurrency() ? $order->getCurrency() : config('yandexkassy.currency')
            ];

            $order_id = $order->getOrderId();
        } else {
            $amount = [
                'value' => $order['total'],
                'currency' => !empty($order['currency']) ? $order['currency'] : config('yandexkassy.currency')
            ];

            $order_id = $order['id'];
        }

        $paymentArray = [
            'amount' => $amount,
            'capture' => $capture,
            'client_ip' => \Request::ip(),
            'metadata' => [
                'order_id' => $order_id
            ]
        ];

        if ($payment_method_data) {
            array_add($paymentArray, 'payment_method_data', $payment_method_data);
        }

        if (!$confirmation) {
            if (config('yandexkassy.confirmation_type') == 'redirect') {
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

        if ($description) {
            array_add($paymentArray, 'description', $description);
        }

        if (config('yandexkassy.receipt.enabled')) {
            if ($order instanceof YandexKassyOrder) {
                $items = [];
                foreach ($order->getProducts() as $product) {
                    if ($product instanceof YandexKassyProducts) {
                        $items[] = [
                            'description' => $product->getProductName(),
                            'quantity' => $product->getProductQuantity(),
                            'amount' => [
                                'value' => $product->getProductPrice(),
                                'currency' => $product->getCurrency() ? $product->getCurrency() : config('yandexkassy.currency')
                            ],
                            'vat_code' => $product->getProductVatCode() ? $product->getProductVatCode() : config('yandexkassy.receipt.vat_code')
                        ];
                    }
                }

                if ($order->getClientEmail() && filter_var($order->getClientEmail(), FILTER_FLAG_EMAIL_UNICODE)) {
                    $flag = 'email';
                    $flagValue = $order->getClientEmail();
                } elseif($order->getClientTelephone()) {
                    $flag = 'phone';
                    $flagValue = $order->getClientTelephone();
                }

            } else {
                $items = $order['products'];

                if ($order['email'] && filter_var($order['email'], FILTER_FLAG_EMAIL_UNICODE)) {
                    $flag = 'email';
                    $flagValue = $order['email'];
                } elseif($order['telephone']) {
                    $flag = 'phone';
                    $flagValue = $order['telephone'];
                }
            }

            $products = [
                'items' => $items
            ];

            if(isset($flag) && isset($flagValue)) {
                array_add($products, $flag, $flagValue);
            }

            array_add($paymentArray, 'receipt', $products);
        }

        $idempotenceKey = UUID::v4();

        try {
            $response = $this->client->createPayment($paymentArray, $idempotenceKey);

            MYandexKassy::create([
                'payment_id' => $response->id,
                'order_id' => $order_id,
                'amount' => $amount['value'],
                'idempotenceKey' => $idempotenceKey
            ]);

            return $response;
        } catch (BadApiRequestException $e) {
            return $e->getMessage();
        } catch (ForbiddenException $e) {
            return $e->getMessage();
        } catch (InternalServerError $e) {
            return $e->getMessage();
        } catch (NotFoundException $e) {
            return $e->getMessage();
        } catch (ResponseProcessingException $e) {
            return $e->getMessage();
        } catch (TooManyRequestsException $e) {
            return $e->getMessage();
        } catch (UnauthorizedException $e) {
            return $e->getMessage();
        } catch (ApiException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * Подтверждение платежа, массив $order должен содержать сумму, которую возьмем
     * с пользователя $order['total'] (если сумма меньше изначальной разница вернется клиенту)
     * и валюту в формате ISO-4217 $order['currency'], если валюта false -
     * возьмем из файла конфигурации
     * @param array|YandexKassyOrder $order
     * @return string|\YandexCheckout\Request\Payments\Payment\CreateCaptureResponse
     */
    public function capturePayment($order)
    {

        if ($order instanceof YandexKassyOrder) {
            $payment = MYandexKassy::whereOrderId($order->getOrderId())->first();
            $currency = $order->getCurrency() ? $order->getCurrency() : config('yandexkassy.currency');
        } else {
            $payment = MYandexKassy::whereOrderId($order['id'])->first();
            $currency = !empty($order['currency']) ? $order['currency'] : config('yandexkassy.currency');
        }

        $amount = [
            'value' => $payment->amount,
            'currency' => $currency
        ];

        try {
            $response = $this->client->capturePayment($amount, $payment->payment_id, $payment->idempotenceKey);

            $payment = MYandexKassy::wherePaymentId($payment->payment_id)->first();

            $payment->update([
                'status' => $response->status
            ]);

            return $response;
        } catch (BadApiRequestException $e) {
            return $e->getMessage();
        } catch (ForbiddenException $e) {
            return $e->getMessage();
        } catch (InternalServerError $e) {
            return $e->getMessage();
        } catch (NotFoundException $e) {
            return $e->getMessage();
        } catch (ResponseProcessingException $e) {
            return $e->getMessage();
        } catch (TooManyRequestsException $e) {
            return $e->getMessage();
        } catch (UnauthorizedException $e) {
            return $e->getMessage();
        } catch (ApiException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * Отмена платежа $order - может быть int orderId
     * @param int|YandexKassyOrder $order
     * @return string|\YandexCheckout\Request\Payments\Payment\CancelResponse
     * @throws \Exception
     */
    public function cancelPayment($order)
    {
        if ($order instanceof YandexKassyOrder) {
            $payment = MYandexKassy::whereOrderId($order->getOrderId())->first();
        } else {
            $payment = MYandexKassy::whereOrderId($order)->first();
        }

        $idempotenceKey = UUID::v4();

        try {
            $response = $this->client->cancelPayment($payment->payment_id, $idempotenceKey);

            $payment = MYandexKassy::wherePaymentId($payment->payment_id)->first();

            $payment->update([
                'status' => $response->status,
                'idempotenceKey' => $idempotenceKey
            ]);

            return $response;
        } catch (BadApiRequestException $e) {
            return $e->getMessage();
        } catch (ForbiddenException $e) {
            return $e->getMessage();
        } catch (InternalServerError $e) {
            return $e->getMessage();
        } catch (NotFoundException $e) {
            return $e->getMessage();
        } catch (ResponseProcessingException $e) {
            return $e->getMessage();
        } catch (TooManyRequestsException $e) {
            return $e->getMessage();
        } catch (UnauthorizedException $e) {
            return $e->getMessage();
        } catch (ApiException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Информация о платеже $order - может быть int orderId
     * @param int|YandexKassyOrder|bool $order
     * @param bool|string $payment_id
     * @return string|\YandexCheckout\Model\PaymentInterface
     */
    public function getPaymentInfo($order, $payment_id = false)
    {
        if(!$order && $payment_id) {
            $payment = MYandexKassy::wherePaymentId($payment_id)->first();
        } elseif ($order instanceof YandexKassyOrder) {
            $payment = MYandexKassy::whereOrderId($order->getOrderId())->first();
        } else {
            $payment = MYandexKassy::whereOrderId($order)->first();
        }

        try {
            return $this->client->getPaymentInfo($payment->payment_id);
        } catch (BadApiRequestException $e) {
            return $e->getMessage();
        } catch (ForbiddenException $e) {
            return $e->getMessage();
        } catch (InternalServerError $e) {
            return $e->getMessage();
        } catch (NotFoundException $e) {
            return $e->getMessage();
        } catch (ResponseProcessingException $e) {
            return $e->getMessage();
        } catch (TooManyRequestsException $e) {
            return $e->getMessage();
        } catch (UnauthorizedException $e) {
            return $e->getMessage();
        } catch (ApiException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Возврат платежа. $order - может быть int orderId
     * @param int|YandexKassyOrder $order
     * @return string|\YandexCheckout\Request\Payments\Payment\CancelResponse
     * @throws \Exception
     */
    public function createRefund($order)
    {
        if($order instanceof YandexKassyOrder) {
            $payment = MYandexKassy::whereOrderId($order->getOrderId())->first();
        } else {
            $payment = MYandexKassy::whereOrderId($order)->first();
        }

        $idempotencyKey = UUID::v4();

        try {
            $response = $this->client->cancelPayment($payment->payment_id, $idempotencyKey);

            $payment->update([
                'status' => $response->status,
                'idempotenceKey' => $idempotencyKey
            ]);

            return $response;
        } catch (BadApiRequestException $e) {
            return $e->getMessage();
        } catch (ForbiddenException $e) {
            return $e->getMessage();
        } catch (InternalServerError $e) {
            return $e->getMessage();
        } catch (NotFoundException $e) {
            return $e->getMessage();
        } catch (ResponseProcessingException $e) {
            return $e->getMessage();
        } catch (TooManyRequestsException $e) {
            return $e->getMessage();
        } catch (UnauthorizedException $e) {
            return $e->getMessage();
        } catch (ApiException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}