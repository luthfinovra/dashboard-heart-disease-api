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
                        ["name" => "Record", "type" => "string"],
                        ["name" => "Annotations", "type" => "string"],
                        ["name" => "Jenis", "type" => "enum", "options" => ["Type1", "Type2"]],
                        ["name" => "Signals", "type" => "string"],
                        ["name" => "Durasi", "type" => "decimal"],
                        ["name" => "Tanggal tes", "type" => "datetime"],
                        ["name" => "File Detak jantung", "type" => "file", "format" => ".wav"]
                    ]
                ],
                'cover_page' => $this->copyStockImage('arrythmia.jpg'),
            ],
            [
                'name' => 'Myocardial',
                'deskripsi' => 'A condition related to heart muscle injury or damage.',
                'schema' => [
                    "columns" => [
                        ["name" => "Nama Pasien", "type" => "string"],
                        ["name" => "Umur", "type" => "integer"],
                        ["name" => "Jenis Kelamin", "type" => "enum", "options" => ["Male", "Female"]],
                        ["name" => "Tanggal lahir", "type" => "date"],
                        ["name" => "Tempat tes", "type" => "string"],
                        ["name" => "Tanggal tes", "type" => "datetime"],
                        ["name" => "Record data", "type" => "file", "format" => ".wav", "multiple" => true]
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
