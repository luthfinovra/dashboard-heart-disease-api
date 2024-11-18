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
                        ["name" => "record", "type" => "string"],
                        ["name" => "annotations", "type" => "string"],
                        ["name" => "jenis", "type" => "enum", "options" => ["Type1", "Type2"]],
                        ["name" => "signals", "type" => "string"],
                        ["name" => "durasi", "type" => "decimal"],
                        ["name" => "tanggal_tes", "type" => "datetime"],
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
                        ["name" => "nama_pasien", "type" => "string"],
                        ["name" => "umur", "type" => "integer"],
                        ["name" => "jenis_kelamin", "type" => "enum", "options" => ["Male", "Female"]],
                        ["name" => "tanggal_lahir", "type" => "date"],
                        ["name" => "tempat_tes", "type" => "string"],
                        ["name" => "tanggal_tes", "type" => "datetime"],
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
