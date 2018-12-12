<?php
namespace Grechanyuk\YandexKassy\Events;

use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class YandexKassyPaymentResult {
    use SerializesModels;

    public $response;

    public function __construct(Request $request)
    {
        $this->response = $request;
    }
}