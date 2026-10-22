<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class GatewayAuth
{
    public function handle(Request $request, Closure $next)
    {
        // 1. القراءة الآمنة لـ Payload
        $payloadStr = $request->input('payload');
        $payload = is_string($payloadStr) ? json_decode($payloadStr, true) : $payloadStr;

        if (!is_array($payload)) {
            $content = json_decode($request->getContent(), true);
            $payloadStr2 = (is_array($content) && isset($content['payload'])) ? $content['payload'] : null;
            $payload = is_string($payloadStr2) ? json_decode($payloadStr2, true) : $payloadStr2;
        }

        // 2. حماية صارمة
        if (!is_array($payload) || !isset($payload['data']['user']['email'])) {
            return response()->json([
                'data' => [
                    'message' => "Non autorisé: Payload invalide",
                ],
                'status' => 401
            ]);
        }

        $email = $payload['data']['user']['email'];

        // 3. نجبدو l'User local
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'data' => ['message' => "Utilisateur non trouvé dans la base locale"],
                'status' => 404
            ]);
        }

        // 4. نلصقو l'User فـ la requête باش تلقاه واجد فـ Controllers
        $request->attributes->add(['gateway_user' => $user]);
        $request->attributes->add(['gateway_payload' => $payload]); // يلا بغيتي تستعملي Les permissions من بعد

        return $next($request);
    }
}