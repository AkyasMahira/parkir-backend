<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\AreaParkir;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleCashi(Request $request)
    {
        $data = $request->all();
        Log::info('Webhook Cashi received:', $data);

        if (isset($data['event']) && $data['event'] == 'PAYMENT_SETTLED') {

            $orderId = $data['data']['order_id'];
            if (strpos($orderId, 'TEST-') === 0) {
                return response()->json(['message' => 'Test connection successful'], 200);
            }

            $trx = Transaksi::where('external_id', $orderId)->first();
            if ($trx && $trx->status_pembayaran !== 'paid') {
                $trx->status_pembayaran = 'paid';
                $trx->save();

                $area = AreaParkir::where('id_area', $trx->id_area)->first();
                if ($area && $area->terisi > 0) {
                    $area->decrement('terisi');
                }
            }

            return response()->json(['status' => 'OK'], 200);
        }

        return response()->json(['status' => 'Ignored'], 200);
    }
}
