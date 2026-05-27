<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class VerifyLodgifySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $headerSignature = $request->header('ms-signature');

        if (!$headerSignature) {
            abort(403, 'Signature manquante.');
        }

        $lodgifySignature = Str::after($headerSignature, 'sha256=');

        $event = $request->query('event');
        $secret = $event === 'new'
            ? env('LODGIFY_WEBHOOK_NEW_SECRET')
            : env('LODGIFY_WEBHOOK_CHANGE_SECRET');

        $payload = $request->getContent();

        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (strtoupper($lodgifySignature) !== strtoupper($computedSignature)) {
            abort(403, 'Signature invalide. Requête suspecte.');
        }

        return $next($request);
    }
}