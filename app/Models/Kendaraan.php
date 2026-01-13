<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    protected $table = 'tb_kendaraan';
    protected $primaryKey = 'id_kendaraan';
    public $timestamps = true;

    protected $fillable = [
        'id_user',
        'plat_nomor',
        'jenis_kendaraan',
        'merk',
        'warna',
        'pemilik',
    ];
}
