<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CamPayService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $mode = config('services.campay.mode');
        $this->baseUrl = $mode === 'prod' 
            ? 'https://www.campay.net/api' 
            : 'https://demo.campay.net/api';
        
        $this->username = config('services.campay.username');
        $this->password = config('services.campay.password');
    }

    /**
     * Get Authorization Token from CamPay
     */
    public function getAccessToken()
    {
        try {
            $response = Http::withoutVerifying()->post("{$this->baseUrl}/token/", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                return $response->json()['token'];
            }

            Log::error('CamPay Token Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('CamPay Connection Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initiate a Mobile Money Collection (USSD Push)
     */
    public function collect($phoneNumber, $amount, $externalReference, $description = 'Paiement Réservation GEV')
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        // Limite stricte de 25 XAF en mode démo
        $mode = config('services.campay.mode');
        if ($mode !== 'prod') {
            $amount = 20; 
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => "Token {$token}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/collect/", [
                'amount' => $amount,
                'currency' => 'XAF',
                'from' => $phoneNumber,
                'description' => $description,
                'external_reference' => $externalReference,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('CamPay Collect Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('CamPay Collect Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check Transaction Status
     */
    public function checkTransactionStatus($reference)
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => "Token {$token}",
            ])->get("{$this->baseUrl}/transaction/{$reference}/");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
