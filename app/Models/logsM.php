<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class logsM extends Model
{
    use HasFactory;
    protected $table = 'logs';
    protected $primaryKey = 'idlogs';
    protected $guarded = [];
}
