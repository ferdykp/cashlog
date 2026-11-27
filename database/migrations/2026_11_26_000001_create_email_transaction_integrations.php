<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 24)->default('gmail');
            $table->string('email');
            $table->text('access_token')->nullable();
            $table->text('refresh_token');
            $table->timestamp('access_token_expires_at')->nullable();
            $table->string('history_id', 80)->nullable();
            $table->timestamp('watch_expires_at')->nullable();
            $table->string('status', 24)->default('connected');
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'provider', 'email']);
            $table->index(['status', 'watch_expires_at']);
        });

        Schema::create('email_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_connection_id')->constrained()->cascadeOnDelete();
            $table->string('bank_code', 40);
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['email_connection_id', 'bank_code'], 'email_mapping_unique');
        });

        Schema::create('email_transaction_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_connection_id')->constrained()->cascadeOnDelete();
            $table->string('gmail_message_id', 255);
            $table->string('bank_code', 40)->nullable();
            $table->string('bank_name', 80)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->enum('type', ['income', 'expense'])->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 24)->default('needs_review');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('review_reason', 180)->nullable();
            $table->timestamps();
            $table->unique(['email_connection_id', 'gmail_message_id'], 'email_import_message_unique');
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_transaction_imports');
        Schema::dropIfExists('email_account_mappings');
        Schema::dropIfExists('email_connections');
    }
};
