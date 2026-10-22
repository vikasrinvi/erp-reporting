<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaintenanceTicketDocumentController extends Controller
{
    private const STAGES = [
        'request',
        'inspection',
        'expert_assessment',
        'budget_approval',
        'repair',
        'verification',
        'other',
    ];

    public function store(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:20'],

            'documents.*.stage' => [
                'required',
                Rule::in(self::STAGES),
            ],

            'documents.*.document_name' => [
                'required',
                'string',
                'max:255',
            ],

            'documents.*.file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,txt',
                'max:10240',
            ],
        ], [
            'documents.*.file.max' =>
                'Each document must be 10 MB or smaller.',
            'documents.*.file.mimes' =>
                'Allowed files: PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX and TXT.',
        ]);

        foreach ($validated['documents'] as $item) {
            $file = $item['file'];

            $path = $file->store(
                'maintenance-tickets/' . $maintenanceTicket->id,
                'local'
            );

            if (!$path) {
                throw new \RuntimeException(
                    'The document could not be saved.'
                );
            }

            MaintenanceTicketDocument::create([
                'maintenance_ticket_id' => $maintenanceTicket->id,
                'stage' => $item['stage'],
                'document_name' => $item['document_name'],
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('maintenance-tickets.show', $maintenanceTicket)
            ->with('success', 'Documents uploaded successfully.');
    }

    public function download(
        MaintenanceTicket $maintenanceTicket,
        MaintenanceTicketDocument $document
    ) {
        // Ensure the document belongs to the ticket in the URL.
        abort_unless(
            (int) $document->maintenance_ticket_id ===
            (int) $maintenanceTicket->id,
            404
        );

        abort_unless(
            Storage::disk('local')->exists($document->file_path),
            404,
            'Document file not found.'
        );

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name
        );
    }
}
