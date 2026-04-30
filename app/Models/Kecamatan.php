<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kecamatan';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'latitude',
        'longitude',
        'elevasi',
        'kode_wilayah',
        'level_rawan',
    ];
}
