<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LodgifyServices
{
    protected string $baseUrl = 'https://api.lodgify.com';

    protected function getHeaders(): array
    {
        return [
            'X-ApiKey' => config('services.lodgify.api_key'),
            'Accept' => 'application/json',
        ];
    }

    public function getBookings(array $filters = []): array
    {
        $response = Http::withoutVerifying()
            ->withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/v1/reservation", $filters);

        return $response->successful() ? ($response->json()['items'] ?? []) : [];
    }

    public function updateBookingNotes($bookingId, $noteTexte)
    {
        try {
            // Utilise l'endpoint PUT v1 partiel avec les headers fonctionnels
            $response = Http::withoutVerifying()
                ->withHeaders($this->getHeaders())
                ->put("{$this->baseUrl}/v1/reservation/booking/{$bookingId}", [
                    'id'    => (int) $bookingId,
                    'note' => $noteTexte
                ]);

            if (!$response->successful()) {
                Log::error("Échec envoi Note Lodgify #{$bookingId}. Statut : " . $response->status() . " | Réponse : " . $response->body());
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("Erreur système communication Notes Lodgify #{$bookingId} : " . $e->getMessage());
            return false;
        }
    }

    public function updateBookingKeyCodeV2($bookingId, $roomTypeId, $passcode)
    {
        try {
            $payload = [
                'rooms' => [
                    [
                        'room_type_id' => (int) $roomTypeId,
                        'key_code' => (string) $passcode
                    ]
                ]
            ];

            $response = Http::withoutVerifying()
                ->withHeaders(array_merge($this->getHeaders(), [
                    'content-type' => 'application/json-patch+json'
                ]))
                ->withBody(json_encode($payload), 'application/json-patch+json')
                ->put("{$this->baseUrl}/v2/reservations/bookings/{$bookingId}/keyCodes");

            if (!$response->successful()) {
                Log::error("Échec PUT v2 keyCodes #{$bookingId}. Statut : " . $response->status() . " | Réponse : " . $response->body());
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error("Erreur système lors du PUT v2 keyCodes #{$bookingId} : " . $e->getMessage());
            return false;
        }
    }
}