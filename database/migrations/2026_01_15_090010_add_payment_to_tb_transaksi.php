<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            // Kolom baru untuk Payment
            $table->string('status_pembayaran', 20)->default('paid')->after('biaya_total'); // paid/pending
            $table->text('qris_content')->nullable()->after('metode_bayar'); // Simpan gambar QR (Base64)
            $table->string('external_id')->nullable()->after('qris_content'); // ID Order Cashi
        });
    }

    public function down(): void
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->dropColumn(['status_pembayaran', 'qris_content', 'external_id']);
        });
    }
};
