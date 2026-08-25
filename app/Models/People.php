<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use LogsActivity;
    protected $fillable = [
        'id',
        'name',
        'amount',
        'start_date',
    ];

}
