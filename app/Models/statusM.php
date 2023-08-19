<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class statusM extends Model
{
    use HasFactory;
    protected $table = 'status';
    protected $primaryKey = 'idstatus';
    protected $guarded = [];
}
