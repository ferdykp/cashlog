<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('accounts', 'provider')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->string('provider', 24)->nullable()->after('type');
            });
        }

        if (Schema::hasTable('email_account_mappings')) {
            $hadLastFour = Schema::hasColumn('email_account_mappings', 'last_four');
            if ($hadLastFour) {
                $seen = [];
                $rows = DB::table('email_account_mappings')->orderByDesc('updated_at')->orderByDesc('id')->get();
                foreach ($rows as $row) {
                    $key = $row->email_connection_id.':'.$row->bank_code;
                    if (isset($seen[$key])) {
                        DB::table('email_account_mappings')->where('id', $row->id)->delete();
                    } else {
                        $seen[$key] = true;
                    }
                }

                Schema::table('email_account_mappings', function (Blueprint $table) {
                    $table->dropUnique('email_mapping_unique');
                    $table->dropColumn(['last_four', 'auto_import']);
                });
                Schema::table('email_account_mappings', function (Blueprint $table) {
                    $table->unique(['email_connection_id', 'bank_code'], 'email_mapping_unique');
                });
            }
        }

        if (Schema::hasTable('email_transaction_imports')) {
            $columns = [];
            foreach (['last_four', 'confidence'] as $column) {
                if (Schema::hasColumn('email_transaction_imports', $column)) {
                    $columns[] = $column;
                }
            }
            if ($columns) {
                Schema::table('email_transaction_imports', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'provider')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('provider');
            });
        }

        if (Schema::hasTable('email_account_mappings')) {
            Schema::table('email_account_mappings', function (Blueprint $table) {
                $table->dropUnique('email_mapping_unique');
                $table->string('last_four', 4)->default('');
                $table->boolean('auto_import')->default(false);
            });
            Schema::table('email_account_mappings', function (Blueprint $table) {
                $table->unique(['email_connection_id', 'bank_code', 'last_four'], 'email_mapping_unique');
            });
        }

        if (Schema::hasTable('email_transaction_imports')) {
            Schema::table('email_transaction_imports', function (Blueprint $table) {
                $table->string('last_four', 4)->nullable();
                $table->decimal('confidence', 4, 2)->default(0);
            });
        }
    }
};
