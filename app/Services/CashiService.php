<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CashiService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('CASHI_API_KEY');
        $this->baseUrl = env('CASHI_BASE_URL');
    }

    // Fungsi Minta QR Code ke Cashi
    public function createOrder($orderId, $amount)
    {
        if ($amount < 2000) $amount = 2000; // Minimal transfer 2000

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY' => $this->apiKey
            ])->post($this->baseUrl . '/create-order', [
                'order_id' => $orderId,
                'amount' => (int) $amount
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json() // Dapat QR Image & Nominal Unik
                ];
            }
            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Fungsi Cek Status Pembayaran
    public function checkStatus($orderId)
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey
            ])->get($this->baseUrl . '/check-status/' . $orderId);

            if ($response->successful()) {
                return $response->json(); 
            }
            return ['success' => false];
        } catch (\Exception $e) {
            return ['success' => false];
        }
    }
}