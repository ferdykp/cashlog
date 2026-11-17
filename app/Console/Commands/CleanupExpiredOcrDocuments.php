<?php

namespace App\Console\Commands;

use App\Models\TransactionImport;
use App\Services\Ocr\OcrDocumentRetentionService;
use Illuminate\Console\Command;

class CleanupExpiredOcrDocuments extends Command
{
    protected $signature = 'ocr:cleanup-expired {--dry-run : Show expired OCR documents without deleting them}';

    protected $description = 'Delete expired OCR source files while retaining import metadata';

    public function handle(OcrDocumentRetentionService $retention): int
    {
        if ($this->option('dry-run')) {
            $count = TransactionImport::query()
                ->whereNotNull('file_expires_at')
                ->whereNull('file_deleted_at')
                ->where('file_expires_at', '<=', now())
                ->count();

            $this->info("{$count} expired OCR source file(s) would be deleted.");

            return self::SUCCESS;
        }

        $count = $retention->deleteExpired();
        $this->info("Deleted {$count} expired OCR source file(s); import records were retained.");

        return self::SUCCESS;
    }
}
