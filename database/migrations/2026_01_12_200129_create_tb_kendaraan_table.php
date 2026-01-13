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
        Schema::create('tb_kendaraan', function (Blueprint $table) {
            $table->id('id_kendaraan');
            $table->unsignedBigInteger('id_user');
            $table->string('plat_nomor', 15)->unique(); 
            $table->string('jenis_kendaraan', 20); 
            $table->string('merk', 50)->nullable(); 
            $table->string('warna', 30)->nullable(); 
            $table->string('pemilik', 100)->nullable(); 

            $table->timestamps();

            // Foreign Key
            $table->foreign('id_user')->references('id_user')->on('tb_user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_kendaraan');
    }
};
