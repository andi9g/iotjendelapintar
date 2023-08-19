<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class alatM extends Model
{
    use HasFactory;
    protected $table = 'alat';
    protected $primaryKey = 'idalat';
    protected $guarded = [];
}
