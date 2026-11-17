<?php

namespace Tests\Feature;

use App\Models\TransactionImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OcrRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function import(User $user, ?string $expiresAt, string $path): TransactionImport
    {
        Storage::disk('private')->put($path, 'private OCR source');

        return TransactionImport::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'file_expires_at' => $expiresAt,
            'ocr_raw_text' => 'kept metadata',
            'parsed_data' => ['amount' => '123.45'],
            'processed_at' => now(),
        ]);
    }

    public function test_owner_can_delete_private_source_while_import_metadata_is_retained(): void
    {
        $owner = User::factory()->create();
        $import = $this->import($owner, now()->addDay()->toDateTimeString(), 'transaction-imports/owner.jpg');

        $this->actingAs($owner)
            ->deleteJson("/api/imports/{$import->id}/document")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        Storage::disk('private')->assertMissing($import->file_path);
        $this->assertDatabaseHas('transaction_imports', [
            'id' => $import->id,
            'ocr_raw_text' => 'kept metadata',
            'file_deleted_at' => $import->fresh()->file_deleted_at->toDateTimeString(),
        ]);
    }

    public function test_another_user_cannot_delete_an_import_source(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $import = $this->import($owner, now()->addDay()->toDateTimeString(), 'transaction-imports/private.jpg');

        $this->actingAs($other)
            ->deleteJson("/api/imports/{$import->id}/document")
            ->assertNotFound();

        Storage::disk('private')->assertExists($import->file_path);
        $this->assertNull($import->fresh()->file_deleted_at);
    }

    public function test_scheduled_cleanup_deletes_only_expired_files_and_keeps_metadata(): void
    {
        $user = User::factory()->create();
        $expired = $this->import($user, now()->subSecond()->toDateTimeString(), 'transaction-imports/expired.jpg');
        $current = $this->import($user, now()->addDay()->toDateTimeString(), 'transaction-imports/current.jpg');
        $legacy = $this->import($user, null, 'transaction-imports/legacy.jpg');

        $this->artisan('ocr:cleanup-expired')
            ->expectsOutput('Deleted 1 expired OCR source file(s); import records were retained.')
            ->assertSuccessful();

        Storage::disk('private')->assertMissing($expired->file_path);
        Storage::disk('private')->assertExists($current->file_path);
        Storage::disk('private')->assertExists($legacy->file_path);
        $this->assertNotNull($expired->fresh()->file_deleted_at);
        $this->assertNull($current->fresh()->file_deleted_at);
        $this->assertNull($legacy->fresh()->file_deleted_at);
        $this->assertSame('kept metadata', $expired->fresh()->ocr_raw_text);
    }

    public function test_cleanup_dry_run_reports_without_deleting_any_source_file(): void
    {
        $user = User::factory()->create();
        $import = $this->import($user, now()->subDay()->toDateTimeString(), 'transaction-imports/dry-run.jpg');

        $this->artisan('ocr:cleanup-expired --dry-run')
            ->expectsOutput('1 expired OCR source file(s) would be deleted.')
            ->assertSuccessful();

        Storage::disk('private')->assertExists($import->file_path);
        $this->assertNull($import->fresh()->file_deleted_at);
    }
}
