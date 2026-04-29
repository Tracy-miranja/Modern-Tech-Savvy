<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'company',
        'position',
        'years',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}

