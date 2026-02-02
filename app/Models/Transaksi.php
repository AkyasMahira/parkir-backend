<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'id_transaksi';
    public $timestamps = true;

    protected $fillable = [
        'struk_id',
        'order_id',
        'id_user',
        'id_area',
        'plat_nomor',
        'jenis_kendaraan',
        'waktu_masuk',
        'waktu_keluar',
        'durasi_jam',
        'biaya_total',
        'status',
        'metode_bayar',
        'foto_identitas',
    ];

    protected $casts = [
        'waktu_masuk' => 'datetime',
        'waktu_keluar' => 'datetime',
        'biaya_total' => 'decimal:2',
        'durasi_jam' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function area()
    {
        return $this->belongsTo(AreaParkir::class, 'id_area', 'id_area');
    }

    public function kendaraan()
    {
        return $this->hasOne(Kendaraan::class, 'plat_nomor', 'plat_nomor');
    }
}
