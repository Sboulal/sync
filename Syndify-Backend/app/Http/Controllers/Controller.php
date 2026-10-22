<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // 1. Fonction bach tjibi Data dyal l'formulaire
    protected function getGatewayData(Request $request)
    {
        return json_decode($request->input('data'), true);
    }

    // 2. Fonction bach tjibi l'User info mn l'IDP
    protected function getGatewayUser(Request $request)
    {
        $payload = json_decode($request->input('payload'), true);
        return $payload['data']['user'] ?? null;
    }

    // 3. Fonction bach t9adi l'réponse kima taleb l'Gateway
    protected function idpResponse($message = "Success", $status = 200, $extraData = [])
    {
        return [
            'data' => array_merge(['message' => $message], $extraData),
            'status' => $status
        ];
    }
}