<?php

namespace App\Helpers;

use Illuminate\Support\Facades\URL;

class SignedUrlGenerator
{
    public function generateDiseaseRecordsExportUrl($diseaseId, $expiryMinutes = 30)
    {
        return URL::temporarySignedRoute(
            'disease.records.export',
            now()->addMinutes($expiryMinutes),
            ['diseaseId' => $diseaseId]
        );
    }

    public function generateSingleRecordExportUrl($diseaseId, $recordId, $expiryMinutes = 30)
    {
        return URL::temporarySignedRoute(
            'disease.record.export',
            now()->addMinutes($expiryMinutes),
            ['diseaseId' => $diseaseId, 'recordId' => $recordId]
        );
    }

    public function generateFileDownloadUrl($path, $expiryMinutes = 30)
    {
        return URL::temporarySignedRoute(
            'files.download.record',
            now()->addMinutes($expiryMinutes),
            ['path' => $path]
        );
    }

}
