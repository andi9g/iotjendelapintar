<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jadwalM extends Model
{
    use HasFactory;
    protected $table = 'jadwal';
    protected $primaryKey = 'idjadwal';
    protected $guarded = [];
}
