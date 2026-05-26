<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TTLockServices
{
    protected string $baseUrl = 'https://euapi.ttlock.com';

    public function getAccessToken(): ?string
    {
        return Cache::remember('ttlock_access_token', now()->addDays(20), function () {

            $response = Http::withoutVerifying()
                ->asForm()
                ->post("{$this->baseUrl}/oauth2/token", [
                    'clientId' => config('services.ttlock.client_id'),
                    'clientSecret' => config('services.ttlock.client_secret'),
                    'grant_type' => 'password',
                    'username' => config('services.ttlock.username'),
                    'password' => md5(config('services.ttlock.password')),
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            return null;
        });
    }

    public function getLocks(): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return ['error' => 'Impossible de récupérer l\'access token.'];
        }

        $milliseconds = (int) (now()->timestamp * 1000);

        $response = Http::withoutVerifying()
            ->asForm()
            ->post("{$this->baseUrl}/v3/key/list", [
                'clientId' => config('services.ttlock.client_id'),
                'accessToken' => $token,
                'pageNo' => 1,
                'pageSize' => 100,
                'date' => $milliseconds,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => 'Erreur HTTP ' . $response->status(),
            'body' => $response->body()
        ];
    }

    public function createCustomPasscode(int $lockId, string $keyboardPwd, string $startDate, string $endDate, $passcodeName= 'Voyageur Lodgify'): array
    {
        $token = $this->getAccessToken();

        $startTimeMs = strtotime($startDate) * 1000;
        $endTimeMs = strtotime($endDate) * 1000;

        $responce = Http::withoutVerifying()->asForm()->post("{$this->baseUrl}/v3/keyboardPwd/add", [
            'clientId' => config('services.ttlock.client_id'),
            'accessToken' => $token,
            'lockId' => $lockId,
            'keyboardPwdName' => $passcodeName,
            'keyboardPwd' => $keyboardPwd,
            'startDate' => $startTimeMs,
            'endDate' => $endTimeMs,
            'addType' => 2,
            'date' => (int) (now()->timestamp * 1000),
        ]);

        return $responce->successful() ? $responce->json() : ['error' => 'Échec de la création du code'];
    }

    public function changePasscodeDates(int $lockId, string $keyboardPwdId, string $startDate, string $endDate): array
    {
        $token = $this->getAccessToken();

        $startTimeMs = strtotime($startDate) * 1000;
        $endTimeMs = strtotime($endDate) * 1000;

        $responce = Http::withoutVerifying()->asForm()->post("{$this->baseUrl}/v3/keyboardPwd/change", [
            'clientId' => config('services.ttlock.client_id'),
            'accessToken' => $token,
            'lockId' => $lockId,
            'keyboardPwdId' => $keyboardPwdId,
            'changeType' => 2,
            'startDate' => $startTimeMs,
            'endDate' => $endTimeMs,
            'date' => (int) (now()->timestamp * 1000),
        ]);

        return $responce->successful() ? $responce->json() : ['error' => 'Échec de la modification du code'];
    }

    public function deletePasscode(int $lockId, string $keyboardPwdId): array
    {
        $token = $this->getAccessToken();

        $responce = Http::withoutVerifying()->asForm()->post("{$this->baseUrl}/v3/keyboardPwd/delete", [
            'clientId' => config('services.ttlock.client_id'),
            'accessToken' => $token,
            'lockId' => $lockId,
            'keyboardPwdId' => $keyboardPwdId,
            'deleteType' => 2,
            'date' => (int) (now()->timestamp * 1000),
        ]);

        return $responce->successful() ? $responce->json() : ['error' => 'Échec de la supression du code'];
    }
}