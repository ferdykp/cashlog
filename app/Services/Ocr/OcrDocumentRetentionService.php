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

        $import->forceFill(['file_deleted_at' => now()])->save();

        return true;
    }
}
