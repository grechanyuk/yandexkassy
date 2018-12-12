<?php
namespace Grechanyuk\YandexKassy\Api;

use Grechanyuk\YandexKassy\Events\YandexKassyPaymentResult;
use Grechanyuk\YandexKassy\Facades\YandexKassy;
use Illuminate\Http\Request;
use YandexCheckout\Model\PaymentInterface;

class EventsController {
    public function responseFromYandexKassy(Request $request) {
        $payment = YandexKassy::getPaymentInfo(false, $request->object->id);

        if($payment instanceof PaymentInterface) {
            if($payment->status == $request->status) {
                event(new YandexKassyPaymentResult($request));
            }
        }
    }
}