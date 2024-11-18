<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Disease;

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
                'cover_page' => $this->copyStockImage('arrythmia.jpg'),
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
                'cover_page' => $this->copyStockImage('myocardial.jpg'),
            ]
        ];

        foreach ($diseases as $disease) {
            Disease::create($disease);
        }
    }

    private function copyStockImage($filename)
    {
        $sourcePath = database_path('seeders/images/' . $filename);
        $destinationPath = 'public/diseases/covers/' . $filename;

        if (file_exists($sourcePath)) {
            Storage::put($destinationPath, file_get_contents($sourcePath));
            return 'diseases/covers/' . $filename;
        }

        return null;
    }
}
