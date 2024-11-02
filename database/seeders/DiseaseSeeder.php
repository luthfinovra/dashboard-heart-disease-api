<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disease;

class DiseaseSeeder extends Seeder
{
    public function run()
    {
        $diseases = [
            [
                'name' => 'Arrhythmia',
                'deskripsi' => 'A condition where the heart beats with an irregular or abnormal rhythm.',
                'schema' => json_encode([
                    "columns" => [
                        ["name" => "Record", "type" => "string"],
                        ["name" => "Annotations", "type" => "string"],
                        ["name" => "Jenis", "type" => "enum", "options" => ["Type1", "Type2"]],
                        ["name" => "Signals", "type" => "string"],
                        ["name" => "Durasi", "type" => "decimal"],
                        ["name" => "Tanggal tes", "type" => "date"],
                        ["name" => "File Detak jantung", "type" => "file", "format" => ".wav"]
                    ]
                ]),
                'cover_page' => null,
            ],
            [
                'name' => 'Myocardial',
                'deskripsi' => 'A condition related to heart muscle injury or damage.',
                'schema' => json_encode([
                    "columns" => [
                        ["name" => "Nama Pasien", "type" => "string"],
                        ["name" => "Umur", "type" => "integer"],
                        ["name" => "Jenis Kelamin", "type" => "enum", "options" => ["Male", "Female"]],
                        ["name" => "Tanggal lahir", "type" => "date"],
                        ["name" => "Tempat tes", "type" => "string"],
                        ["name" => "Tanggal tes", "type" => "date"],
                        ["name" => "Record data", "type" => "file", "format" => ".wav", "multiple" => true]
                    ]
                ]),
                'cover_page' => null,
            ]
        ];

        foreach ($diseases as $diseaseData) {
            Disease::create($diseaseData);
        }
    }
}
