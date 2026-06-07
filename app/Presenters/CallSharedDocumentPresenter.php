<?php

namespace App\Presenters;

use App\Models\MedicalDocument;

class CallSharedDocumentPresenter
{
    public function forPatient(MedicalDocument $document): array
    {
        $downloadUrl = route('patient.medical-records.documents.download', $document);

        return $this->base($document) + [
            'download_url' => $downloadUrl,
            'view_url' => $downloadUrl.'?disposition=inline',
        ];
    }

    public function forDoctor(MedicalDocument $document): array
    {
        $downloadUrl = route('doctor.patients.medical-record.documents.download', [
            'patient' => $document->patient_id,
            'document' => $document,
        ]);

        return $this->base($document) + [
            'download_url' => $downloadUrl,
            'view_url' => $downloadUrl.'?disposition=inline',
        ];
    }

    private function base(MedicalDocument $document): array
    {
        return [
            'id' => $document->id,
            'category' => $document->category,
            'name' => $document->name,
            'file_type' => $document->file_type,
            'file_size' => $document->file_size,
            'visibility' => $document->visibility,
            'created_at' => $document->created_at->format('c'),
        ];
    }
}
