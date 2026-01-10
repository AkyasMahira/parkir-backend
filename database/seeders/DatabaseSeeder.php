<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tarif;
use App\Models\AreaParkir;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun sesuai Role (Password: password)
        User::create([
            'username' => 'admin',
            'password' => Hash::make('password'),
            'nama_lengkap' => 'Administrator Sistem',
            'role' => 'admin',
        ]);

        User::create([
            'username' => 'petugas',
            'password' => Hash::make('password'),
            'nama_lengkap' => 'Budi Petugas Parkir',
            'role' => 'petugas',
        ]);

        User::create([
            'username' => 'owner',
            'password' => Hash::make('password'),
            'nama_lengkap' => 'Bapak Pemilik',
            'role' => 'owner',
        ]);

        // Tarif Parkir Dasar
        Tarif::create([
            'jenis_kendaraan' => 'motor',
            'tarif_per_jam' => 2000,
        ]);

        Tarif::create([
            'jenis_kendaraan' => 'mobil',
            'tarif_per_jam' => 5000,
        ]);

        Tarif::create([
            'jenis_kendaraan' => 'truk',
            'tarif_per_jam' => 8000,
        ]);

        // Area Parkir
        AreaParkir::create([
            'nama_area' => 'Lantai 1 (Utama)',
            'kapasitas' => 50,
            'terisi' => 0,
        ]);
    }
}
