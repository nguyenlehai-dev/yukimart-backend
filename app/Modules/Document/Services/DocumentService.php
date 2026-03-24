<?php

namespace App\Modules\Document\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Document\Enums\DocumentStatusEnum;
use App\Modules\Document\Exports\DocumentsExport;
use App\Modules\Document\Imports\DocumentsImport;
use App\Modules\Document\Models\Document;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentService
{
    public function __construct(private MediaService $mediaService) {}

    public function stats(array $filters): array
    {
        $base = Document::filter($filters);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', DocumentStatusEnum::Active->value)->count(),
            'inactive' => (clone $base)->where('status', DocumentStatusEnum::Inactive->value)->count(),
        ];
    }

    public function index(array $filters, int $limit)
    {
        return Document::with(['issuingAgency', 'issuingLevel', 'signer', 'types', 'fields'])
            ->filter($filters)
            ->paginate($limit);
    }

    public function show(Document $document): Document
    {
        return $document->load(['issuingAgency', 'issuingLevel', 'signer', 'types', 'fields', 'media', 'creator', 'editor']);
    }

    public function store(array $validated, array $attachments = []): Document
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($validated, $attachments, &$storedFiles) {
                $data = collect($validated)->except(['document_type_ids', 'document_field_ids', 'attachments'])->all();
                $document = Document::create($data);

                $document->types()->sync($validated['document_type_ids'] ?? []);
                $document->fields()->sync($validated['document_field_ids'] ?? []);

                $uploaded = $this->mediaService->uploadMany($document, $attachments, 'document-attachments', [
                    'disk' => 'public',
                ]);
                $storedFiles = array_merge($storedFiles, $uploaded);

                return $document->load(['issuingAgency', 'issuingLevel', 'signer', 'types', 'fields', 'media', 'creator', 'editor']);
            });
        } catch (\Throwable $exception) {
            $this->mediaService->cleanupStoredFiles($storedFiles);
            throw $exception;
        }
    }

    public function update(Document $document, array $validated, array $attachments = []): Document
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($document, $validated, $attachments, &$storedFiles) {
                $data = collect($validated)->except(['document_type_ids', 'document_field_ids', 'attachments', 'remove_attachment_ids'])->all();
                $document->update($data);

                if (array_key_exists('document_type_ids', $validated)) {
                    $document->types()->sync($validated['document_type_ids'] ?? []);
                }

                if (array_key_exists('document_field_ids', $validated)) {
                    $document->fields()->sync($validated['document_field_ids'] ?? []);
                }

                if (! empty($validated['remove_attachment_ids'])) {
                    $this->mediaService->removeByIds($document, $validated['remove_attachment_ids'], 'document-attachments');
                }

                $uploaded = $this->mediaService->uploadMany($document, $attachments, 'document-attachments', [
                    'disk' => 'public',
                ]);
                $storedFiles = array_merge($storedFiles, $uploaded);

                return $document->load(['issuingAgency', 'issuingLevel', 'signer', 'types', 'fields', 'media', 'creator', 'editor']);
            });
        } catch (\Throwable $exception) {
            $this->mediaService->cleanupStoredFiles($storedFiles);
            throw $exception;
        }
    }

    public function destroy(Document $document): void
    {
        $document->delete();
    }

    public function bulkDestroy(array $ids): void
    {
        Document::whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): void
    {
        Document::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function changeStatus(Document $document, string $status): Document
    {
        $document->update(['status' => $status]);

        return $document->load(['issuingAgency', 'issuingLevel', 'signer', 'types', 'fields', 'media', 'creator', 'editor']);
    }

    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new DocumentsExport($filters), 'documents.xlsx');
    }

    public function import($file): void
    {
        Excel::import(new DocumentsImport, $file);
    }
}
