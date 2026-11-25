<?php

namespace App\Console\Commands;

use App\Models\TransactionImport;
use App\Services\Ocr\OcrDocumentRetentionService;
use Illuminate\Console\Command;

class CleanupExpiredOcrDocuments extends Command
{
    protected $signature = 'ocr:cleanup-expired {--dry-run : Show expired OCR documents without deleting them}';

    protected $description = 'Delete expired OCR source files and scrub retained OCR results';

    public function handle(OcrDocumentRetentionService $retention): int
    {
        if ($this->option('dry-run')) {
            $count = TransactionImport::query()
                ->whereNotNull('file_expires_at')
                ->whereNull('file_deleted_at')
                ->where('file_expires_at', '<=', now())
                ->count();
            $legacyCutoff = now()->subDays((int) config('services.ocr.retention_days', 30));
            $resultCount = TransactionImport::query()
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
                ->count();

            $this->info("{$count} expired OCR source file(s) would be deleted; {$resultCount} OCR result row(s) would be scrubbed.");

            return self::SUCCESS;
        }

        $count = $retention->deleteExpired();
        $this->info("Deleted {$count} expired OCR source file(s) and scrubbed expired OCR results.");

        return self::SUCCESS;
    }
}
