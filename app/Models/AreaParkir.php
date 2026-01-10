<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaParkir extends Model
{
    protected $table = 'tb_area_parkir';
    protected $primaryKey = 'id_area';
    protected $fillable = ['nama_area', 'kapasitas', 'terisi'];
}