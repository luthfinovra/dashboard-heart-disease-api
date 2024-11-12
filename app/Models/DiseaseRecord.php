<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiseaseRecord extends Model
{
    use HasFactory;

    protected $fillable = ['disease_id', 'data'];

    protected $casts = [
        'data' => 'json',
    ];

    public function disease()
    {
        return $this->belongsTo(Disease::class);
    }
}
