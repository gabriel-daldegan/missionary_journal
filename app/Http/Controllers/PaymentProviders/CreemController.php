<?php

namespace App\Http\Controllers\PaymentProviders;

use App\Http\Controllers\Controller;
use App\Services\PaymentProviders\Creem\CreemWebhookHandler;
use Illuminate\Http\Request;

class CreemController extends Controller
{
    public function handleWebhook(Request $request, CreemWebhookHandler $handler)
    {
        return $handler->handleWebhook($request);
    }
}
