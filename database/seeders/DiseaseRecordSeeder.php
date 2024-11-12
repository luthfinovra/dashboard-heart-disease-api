<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disease;
use App\Models\DiseaseRecord;
use Carbon\Carbon;

class DiseaseRecordSeeder extends Seeder
{
    public function run()
    {
        $diseases = Disease::all();

        foreach ($diseases as $disease) {
            $records = [];

            for ($i = 0; $i < 10; $i++) {
                $recordData = match($disease->name) {
                    'Arrhythmia' => [
                        'Record' => 'Record_' . $i,
                        'Annotations' => 'Annotation_' . $i,
                        'Jenis' => collect(['Type1', 'Type2'])->random(),
                        'Signals' => 'Signal_' . $i,
                        'Durasi' => rand(5, 20) + (rand(0, 99) / 100),
                        'Tanggal tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                        'File Detak jantung' => null,
                    ],
                    'Myocardial' => [
                        'Nama Pasien' => 'Patient_' . $i,
                        'Umur' => rand(30, 70),
                        'Jenis Kelamin' => collect(['Male', 'Female'])->random(),
                        'Tanggal lahir' => Carbon::now()->subYears(rand(30, 70))->toDateString(),
                        'Tempat tes' => 'Hospital_' . rand(1, 10),
                        'Tanggal tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                        'Record data' => null,
                    ],
                    default => [],
                };

                $records[] = [
                    'disease_id' => $disease->id,
                    'data' => json_encode($recordData),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DiseaseRecord::insert($records);
        }
    }
}
