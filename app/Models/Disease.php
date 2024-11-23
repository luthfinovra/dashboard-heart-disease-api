<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Disease extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'deskripsi',
        'schema',
        'cover_page'
    ];

    protected $casts = [
        'schema' => 'json' 
    ];

    public function operators()
    {
        return $this->belongsToMany(User::class, 'disease_operator', 'disease_id', 'user_id');
    }

    public function diseaseRecords()
    {
        return $this->hasMany(DiseaseRecord::class);
    }

    protected function getCoverPageUrlAttribute(): ?string
    {
        if ($this->cover_page) {
            //$domain = env('APP_URL');
            return Storage::url('public/' . $this->cover_page);
        }
        return null;
    }
    
    protected function getExportUrlAttribute(): string
    {
        return url("/api/diseases/{$this->id}/export");
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['cover_page_url'] = $this->cover_page_url;
        $array['export_url'] = $this->export_url;
        return $array;
    }

}
