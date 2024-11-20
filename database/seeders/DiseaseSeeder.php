<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Disease;
use Illuminate\Support\Str;

class DiseaseSeeder extends Seeder
{
    public function run()
    {
        Storage::makeDirectory('public/diseases/covers');

        $diseases = [
            [
                'name' => 'Arrhythmia',
                'deskripsi' => 'A condition where the heart beats with an irregular or abnormal rhythm.',
                'schema' => [
                    "columns" => [
                        ["name" => "record", "type" => "string", "is_visible" => true],
                        ["name" => "annotations", "type" => "string", "is_visible" => true],
                        ["name" => "jenis", "type" => "string", /*"options" => ["Type1", "Type2"]*/ "is_visible" => true],
                        ["name" => "signals", "type" => "string", "is_visible" => true],
                        ["name" => "durasi", "type" => "decimal", "is_visible" => true],
                        ["name" => "tanggal_tes", "type" => "datetime", "is_visible" => true],
                        ["name" => "file_detak_jantung", "type" => "file", "format" => ".wav"]
                    ]
                ],
                'cover_page' => null,
            ],
            [
                'name' => 'Myocardial',
                'deskripsi' => 'A condition related to heart muscle injury or damage.',
                'schema' => [
                    "columns" => [
                        ["name" => "nama_pasien", "type" => "string", "is_visible" => true],
                        ["name" => "umur", "type" => "integer", "is_visible" => true],
                        ["name" => "jenis_kelamin", "type" => "string", /*"options" => ["Male", "Female"]*/ "is_visible" => true],
                        ["name" => "tanggal_lahir", "type" => "date", "is_visible" => true],
                        ["name" => "tempat_tes", "type" => "string", "is_visible" => true],
                        ["name" => "tanggal_tes", "type" => "datetime", "is_visible" => true],
                        ["name" => "record_data", "type" => "file", "format" => ".wav", "multiple" => true]
                    ]
                ],
                'cover_page' => null,
            ]
        ];

        foreach ($diseases as $disease) {
            $createdDisease = Disease::create($disease);
            
            $imageName = Str::slug($disease['name']) . '.jpg';
            $sourcePath = database_path('seeders/images/' . $imageName);
            
            if (file_exists($sourcePath)) {
                $destinationPath = "diseases/covers/disease-{$createdDisease->id}-cover.jpg";
                Storage::put('public/' . $destinationPath, file_get_contents($sourcePath));
                $createdDisease->update(['cover_page' => $destinationPath]);
            }
        }
    }
}
