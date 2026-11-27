<?php

namespace App\Console\Commands;

use App\Jobs\SyncGmailConnectionJob;
use App\Models\EmailConnection;
use Illuminate\Console\Command;

class SyncGmailConnections extends Command
{
    protected $signature = 'gmail:sync';

    protected $description = 'Queue connected Gmail mailboxes for periodic synchronization.';

    public function handle(): int
    {
        if (! config('services.gmail.enabled')) {
            return self::SUCCESS;
        }

        EmailConnection::whereIn('status', ['connected', 'error'])->orderBy('id')->chunkById(50, function ($connections) {
            foreach ($connections as $connection) {
                SyncGmailConnectionJob::dispatch($connection->id);
            }
        });

        return self::SUCCESS;
    }
}
