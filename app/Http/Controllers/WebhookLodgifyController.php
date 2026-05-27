<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LockSyncServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class WebhookLodgifyController extends Controller
{
    protected $lockSyncServices;

    public function __construct(LockSyncServices $lockSyncServices)
    {
        $this->lockSyncServices = $lockSyncServices;
    }

    public function handle(Request $request)
    {
        $reservationData = $request->all();

        if (empty($reservationData)) {
            return response()->json(['message' => 'Contenu vide'], 400);
        }

        $report = $this->lockSyncServices->handleSynchronization($reservationData);

        Log::info("Webhook Lodgify traité", $report);
        \Log::info('Contenu brut du Webhook reçu :', $request->all());

        return response()->json(['status' => 'success', 'report' => $report]);
    }



    public function registerWebhooks()
    {
        $apiKey = env('LODGIFY_API_KEY');
        $baseUrl = "https://api.lodgify.com/webhooks/v1/subscribe";

        // Ton URL publique Ngrok de réception
        $myWebhookUrl = "https://limb-expansive-dairy.ngrok-free.dev/api/webhook/lodgify";

        // 1. Abonnement aux nouvelles réservations
        $responseNew = Http::withoutVerifying()
            ->withHeaders([
                'X-ApiKey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl, [
                    'target_url' => $myWebhookUrl . '?event=new',
                    'event' => 'booking_new_any_status'
                ]);

        // 2. Abonnement aux modifications de réservations
        $responseChange = Http::withoutVerifying()
            ->withHeaders([
                'X-ApiKey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl, [
                    'target_url' => $myWebhookUrl . '?event=change',
                    'event' => 'booking_change'
                ]);

        return response()->json([
            'webhook_new_status' => $responseNew->status(),
            'webhook_new_response' => $responseNew->json(),
            'webhook_change_status' => $responseChange->status(),
            'webhook_change_response' => $responseChange->json(),
        ]);
    }
}