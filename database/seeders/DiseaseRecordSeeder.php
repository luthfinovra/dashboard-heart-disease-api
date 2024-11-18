<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Disease;
use App\Models\DiseaseRecord;
use Carbon\Carbon;

class DiseaseRecordSeeder extends Seeder
{
    private $sampleFiles = [
        'example1.wav' => ['path' => 'diseases/samples/example1.wav', 'mime' => 'audio/wav'],
        'example2.wav' => ['path' => 'diseases/samples/example2.wav', 'mime' => 'audio/wav'],
    ];

    public function run()
    {
        Storage::makeDirectory('diseases/records');
        
        $this->storeSampleFiles();

        $diseases = Disease::all();

        foreach ($diseases as $disease) {
            $records = [];
            
            for ($i = 0; $i < 1000; $i++) {
                $recordData = $this->generateRecordData($disease->name, $i, $disease->id);
                
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

    private function generateRecordData($diseaseName, $index, $diseaseId)
    {
        $basePath = "diseases/records/$diseaseId";
        
        return match($diseaseName) {
            'Arrhythmia' => [
                'record' => 'Record_' . $index,
                'annotations' => 'Annotation_' . $index,
                'jenis' => collect(['Type1', 'Type2'])->random(),
                'signals' => 'Signal_' . $index,
                'durasi' => rand(5, 20) + (rand(0, 99) / 100),
                'tanggal_tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                'file_detak_jantung' => "$basePath/heart_signal_$index.wav",
            ],
            'Myocardial' => [
                'nama_pasien' => 'Patient_' . $index,
                'umur' => rand(30, 70),
                'jenis_kelamin' => collect(['Male', 'Female'])->random(),
                'tanggal_lahir' => Carbon::now()->subYears(rand(30, 70))->toDateString(),
                'tempat_tes' => 'Hospital_' . rand(1, 10),
                'tanggal_tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                'record_data' => ["$basePath/heart_record_$index.wav"],
            ],
            default => [],
        };
    }

    private function storeSampleFiles()
    {
        foreach ($this->sampleFiles as $filename => $fileInfo) {
            $sourcePath = database_path('seeders/samples/' . $filename);
            
            if (file_exists($sourcePath)) {
                Storage::put($fileInfo['path'], file_get_contents($sourcePath));
            } else {
                Storage::put($fileInfo['path'], 'Sample file content for ' . $filename);
            }
        }
    }
}