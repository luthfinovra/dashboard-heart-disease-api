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

            for ($i = 0; $i < 1000; $i++) {
                $recordData = match($disease->name) {
                    'Arrhythmia' => [
                        'record' => 'Record_' . $i,
                        'annotations' => 'Annotation_' . $i,
                        'jenis' => collect(['Type1', 'Type2'])->random(),
                        'signals' => 'Signal_' . $i,
                        'durasi' => rand(5, 20) + (rand(0, 99) / 100),
                        'tanggal_tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                        'file_detak_jantung' => null,
                    ],
                    'Myocardial' => [
                        'nama_pasien' => 'Patient_' . $i,
                        'umur' => rand(30, 70),
                        'jenis_kelamin' => collect(['Male', 'Female'])->random(),
                        'tanggal_lahir' => Carbon::now()->subYears(rand(30, 70))->toDateString(),
                        'tempat_tes' => 'Hospital_' . rand(1, 10),
                        'tanggal_tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                        'record_data' => null,
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
