<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_imports', function (Blueprint $table) {
            $table->timestamp('file_expires_at')->nullable()->index();
            $table->timestamp('file_deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_imports', function (Blueprint $table) {
            $table->dropIndex(['file_expires_at']);
            $table->dropColumn(['file_expires_at', 'file_deleted_at']);
        });
    }
};
