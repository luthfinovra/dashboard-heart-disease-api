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
        // Create the diseases/records directory if it doesn't exist
        Storage::makeDirectory('diseases/records');
        
        $this->storeSampleFiles();

        $diseases = Disease::all();

        foreach ($diseases as $disease) {
            $records = [];
            
            // Loop to create records for the disease
            for ($i = 0; $i < 1000; $i++) {
                $recordData = $this->generateRecordData($disease->name, $i, $disease->id);
                
                $records[] = [
                    'disease_id' => $disease->id,
                    'data' => json_encode($recordData),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert all disease records in batch
            DiseaseRecord::insert($records);
        }
    }

    private function generateRecordData($diseaseName, $index, $diseaseId)
    {
        $basePath = "diseases/records/$diseaseId";
        
        // Use the same file name for all records
        $commonFile1 = 'example1.wav'; // This is the same for all records
        $commonFile2 = 'example2.wav'; // This is the same for all records
    
        return match($diseaseName) {
            'Arrhythmia' => [
                'record' => 'Record_' . $index,
                'annotations' => 'Annotation_' . $index,
                'jenis' => collect(['Type1', 'Type2'])->random(),
                'signals' => 'Signal_' . $index,
                'durasi' => rand(5, 20) + (rand(0, 99) / 100),
                'tanggal_tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                'file_detak_jantung' => "$basePath/$commonFile1", // Same file for all records
            ],
            'Myocardial' => [
                'nama_pasien' => 'Patient_' . $index,
                'umur' => rand(30, 70),
                'jenis_kelamin' => collect(['Male', 'Female'])->random(),
                'tanggal_lahir' => Carbon::now()->subYears(rand(30, 70))->toDateString(),
                'tempat_tes' => 'Hospital_' . rand(1, 10),
                'tanggal_tes' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
                'record_data' => ["$basePath/$commonFile1", "$basePath/$commonFile2"], // Same file for all records
            ],
            default => [],
        };
    }
    

    private function storeSampleFiles()
    {
        // Manually assign the file paths to different disease IDs
        $diseaseRecords = [
            1 => ['example1.wav'],   // Store example1.wav for disease_id 1
            2 => ['example1.wav', 'example2.wav'],   // Store both example1.wav and example2.wav for disease_id 2
        ];
    
        foreach ($diseaseRecords as $diseaseId => $files) {
            $basePath = "public/diseases/records/$diseaseId"; // Dynamic path based on disease_id
            Storage::makeDirectory($basePath); // Ensure the disease-specific record directory exists
    
            foreach ($files as $filename) {
                $sourcePath = database_path('seeders/samples/' . $filename);
                
                if (file_exists($sourcePath)) {
                    // Store the file under the disease's records folder
                    Storage::put("$basePath/$filename", file_get_contents($sourcePath));
                } else {
                    // Handle case where file doesn't exist (use placeholder)
                    Storage::put("$basePath/$filename", 'Sample file content for ' . $filename);
                }
            }
        }
    }
    
}
