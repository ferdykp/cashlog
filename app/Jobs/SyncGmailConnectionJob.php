<?php

namespace App\Jobs;

use App\Models\EmailConnection;
use App\Services\Email\GmailSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGmailConnectionJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public readonly int $connectionId) {}

    public function uniqueId(): string
    {
        return 'gmail-sync-'.$this->connectionId;
    }

    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(GmailSyncService $sync): void
    {
        if (! config('services.gmail.enabled')) {
            return;
        }

        $connection = EmailConnection::whereKey($this->connectionId)->where('status', '!=', 'reauthorize')->first();
        if ($connection) {
            $sync->sync($connection);
        }
    }
}
