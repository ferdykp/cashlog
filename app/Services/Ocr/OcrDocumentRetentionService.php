<?php

namespace App\Services\Ocr;

use App\Models\TransactionImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OcrDocumentRetentionService
{
    public function deleteSourceForOwner(int $importId, int $userId): bool
    {
        return DB::transaction(function () use ($importId, $userId) {
            $import = TransactionImport::query()
                ->whereKey($importId)
                ->lockForUpdate()
                ->first();

            if (! $import || (int) $import->user_id !== $userId) {
                return false;
            }

            return $this->deleteSource($import);
        }, 3);
    }

    public function deleteExpired(): int
    {
        $deleted = 0;

        // OCR text and parsed financial details are only needed while the
        // source document is available for review. Also scrub legacy rows
        // without an expiry after the configured retention window.
        $legacyCutoff = now()->subDays((int) config('services.ocr.retention_days', 30));
        TransactionImport::query()
            ->where(function ($query) use ($legacyCutoff) {
                $query->where(function ($expiring) {
                    $expiring->whereNotNull('file_expires_at')->where('file_expires_at', '<=', now());
                })->orWhere('created_at', '<=', $legacyCutoff);
            })
            ->where(function ($query) {
                $query->whereNotNull('ocr_raw_text')
                    ->orWhereNotNull('parsed_data')
                    ->orWhereNotNull('confidence');
            })
            ->update(['ocr_raw_text' => null, 'parsed_data' => null, 'confidence' => null]);

        TransactionImport::query()
            ->whereNotNull('file_expires_at')
            ->whereNull('file_deleted_at')
            ->where('file_expires_at', '<=', now())
            ->chunkById(100, function ($imports) use (&$deleted) {
                foreach ($imports as $import) {
                    if ($this->deleteSourceForOwner((int) $import->id, (int) $import->user_id)) {
                        $deleted++;
                    }
                }
            });

        return $deleted;
    }

    private function deleteSource(TransactionImport $import): bool
    {
        if ($import->file_deleted_at) {
            return false;
        }

        $disk = Storage::disk('private');
        if ($import->file_path && $disk->exists($import->file_path) && ! $disk->delete($import->file_path)) {
            throw new RuntimeException('Unable to delete the private OCR source file.');
        }

        $import->forceFill([
            'file_deleted_at' => now(),
            'ocr_raw_text' => null,
            'parsed_data' => null,
            'confidence' => null,
        ])->save();

        return true;
    }
}
