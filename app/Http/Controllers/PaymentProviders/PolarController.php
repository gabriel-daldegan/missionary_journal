<?php

namespace App\Http\Controllers\PaymentProviders;

use App\Http\Controllers\Controller;
use App\Services\PaymentProviders\Polar\PolarWebhookHandler;
use Illuminate\Http\Request;

class PolarController extends Controller
{
    public function handleWebhook(Request $request, PolarWebhookHandler $handler)
    {
        return $handler->handleWebhook($request);
    }
}
