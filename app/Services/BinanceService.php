<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BinanceService
{
    protected $apiKey;
    protected $secretKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('BINANCE_API_KEY');
        $this->secretKey = env('BINANCE_SECRET_KEY');
        $this->baseUrl = 'https://api.binance.com';
    }

    public function getBalances()
    {
        $timestamp = round(microtime(true) * 1000);
        $queryString = 'timestamp=' . $timestamp;

        $signature = hash_hmac('sha256', $queryString, $this->secretKey);

        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey,
        ])->get($this->baseUrl . '/api/v3/account', [
            'timestamp' => $timestamp,
            'signature' => $signature
        ]);

        return $response->json();
    }
}
