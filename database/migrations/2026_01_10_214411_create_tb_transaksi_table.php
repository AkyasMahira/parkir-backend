<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_transaksi', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->string('struk_id', 20)->unique();
            $table->string('order_id', 50)->nullable();

            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_area');
            $table->string('plat_nomor', 15);
            $table->string('jenis_kendaraan', 20);

            $table->dateTime('waktu_masuk');
            $table->dateTime('waktu_keluar')->nullable();
            $table->integer('durasi_jam')->nullable();
            $table->decimal('biaya_total', 12, 2)->nullable();

            $table->enum('status', ['parkir', 'selesai', 'menunggu_bayar'])->default('parkir');
            $table->enum('metode_bayar', ['cash', 'qris'])->nullable();
            $table->string('foto_identitas')->nullable();

            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('tb_user')->onDelete('cascade');
            $table->foreign('id_area')->references('id_area')->on('tb_area_parkir')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_transaksi');
    }
};
