<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tb_transaksi', function (Blueprint $table) {
            $table->id('id_transaksi');

            $table->unsignedBigInteger('id_user')->nullable(); 
            $table->unsignedBigInteger('id_kendaraan')->nullable(); 
            $table->unsignedBigInteger('id_area');
            $table->string('plat_nomor', 15);
            $table->string('jenis_kendaraan', 20);

            // Waktu & Biaya
            $table->dateTime('waktu_masuk');
            $table->dateTime('waktu_keluar')->nullable();
            $table->integer('durasi_jam')->default(0);
            $table->decimal('biaya_total', 12, 2)->default(0);

            // Status & Pembayaran
            $table->enum('status', ['masuk', 'keluar']);
            $table->enum('metode_bayar', ['cash', 'qris', 'transfer'])->default('cash');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_transaksi');
    }
};
