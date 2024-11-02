<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'deskripsi',
        'schema',
        'cover_page'
    ];

    public function operators()
    {
        return $this->belongsToMany(User::class, 'disease_operator', 'disease_id', 'user_id');
    }
}
