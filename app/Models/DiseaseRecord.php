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

    protected function getExportUrlAttribute(): string
    {
        return url("/api/diseases/{$this->disease_id}/records/{$this->id}/export");
    }

    protected function getPublicFileUrl($path): string
    {
        if (empty($path)) {
            return '';
        }
        
        return url("/storage/public/{$path}");
    }


    public function toArray()
    {
        $array = parent::toArray();
        $array['export_url'] = $this->export_url;
        return $array;
    }
}