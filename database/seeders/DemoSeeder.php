<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetPrice;
use App\Models\AssetTransaction;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalAssetAllocation;
use App\Models\GoalCashAllocation;
use App\Models\GoalContribution;
use App\Models\GoalItem;
use App\Models\GoalObligation;
use App\Models\Transaction;
use App\Models\TransactionImport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->startOfDay();
        $monthStart = $today->copy()->startOfMonth()->toDateString();

        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'currency' => 'IDR',
                'theme' => 'system',
                'email_verified_at' => $today,
            ],
        );
        $partner = User::updateOrCreate(
            ['email' => 'partner@example.com'],
            [
                'name' => 'Demo Partner',
                'password' => Hash::make('password'),
                'currency' => 'IDR',
                'theme' => 'system',
                'email_verified_at' => $today,
            ],
        );

        $accounts = collect([
            ['name' => 'Tunai', 'type' => 'cash', 'purpose' => 'operational', 'balance' => '1250000.00', 'color' => '#16A34A', 'icon' => 'wallet'],
            ['name' => 'Rekening Utama', 'type' => 'bank', 'purpose' => 'operational', 'balance' => '29205000.00', 'color' => '#2563EB', 'icon' => 'landmark'],
            ['name' => 'Dompet Digital', 'type' => 'ewallet', 'purpose' => 'operational', 'balance' => '640000.00', 'color' => '#7C3AED', 'icon' => 'smartphone'],
            ['name' => 'Dana Tujuan', 'type' => 'bank', 'purpose' => 'goal', 'balance' => '3000000.00', 'color' => '#D97706', 'icon' => 'target'],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => Account::updateOrCreate(
            ['user_id' => $user->id, 'name' => $data['name']], $data + ['user_id' => $user->id],
        )]);

        $categoryData = [
            'Gaji' => ['income', 'briefcase', '#16A34A', null],
            'Freelance' => ['income', 'laptop', '#0D9488', null],
            'Makanan' => ['expense', 'utensils', '#EA580C', null],
            'Transportasi' => ['expense', 'car', '#2563EB', null],
            'Tagihan' => ['expense', 'receipt', '#7C3AED', null],
            'Belanja Rumah' => ['expense', 'shopping-bag', '#DB2777', null],
            'Hiburan' => ['expense', 'film', '#CA8A04', null],
            'Kesehatan' => ['expense', 'heart-pulse', '#DC2626', null],
            'Kopi' => ['expense', 'coffee', '#A16207', 'Makanan'],
            'Bahan Makanan' => ['expense', 'carrot', '#65A30D', 'Belanja Rumah'],
        ];
        $categories = collect();
        foreach ($categoryData as $name => [$type, $icon, $color, $parent]) {
            $parentId = $parent ? $categories->get($parent)?->id : null;
            $categories->put($name, Category::updateOrCreate(
                ['user_id' => $user->id, 'name' => $name, 'type' => $type],
                ['parent_id' => $parentId, 'icon' => $icon, 'color' => $color],
            ));
        }

        $transactionData = [
            ['salary', 'Rekening Utama', 'Gaji', 'income', 18500000, 'Gaji bulan ini', -2],
            ['freelance', 'Rekening Utama', 'Freelance', 'income', 2500000, 'Proyek desain', -5],
            ['groceries', 'Dompet Digital', 'Bahan Makanan', 'expense', 425000, 'Belanja mingguan', -1],
            ['lunch', 'Dompet Digital', 'Kopi', 'expense', 68000, 'Makan siang', -2],
            ['transport', 'Dompet Digital', 'Transportasi', 'expense', 92000, 'Transportasi online', -3],
            ['internet', 'Rekening Utama', 'Tagihan', 'expense', 385000, 'Internet rumah', -4],
            ['household', 'Rekening Utama', 'Belanja Rumah', 'expense', 235000, 'Kebutuhan rumah', -6],
            ['coffee', 'Tunai', 'Kopi', 'expense', 38000, 'Kopi sore', -7],
            ['health', 'Rekening Utama', 'Kesehatan', 'expense', 175000, 'Vitamin dan obat', -8],
            ['entertainment', 'Dompet Digital', 'Hiburan', 'expense', 120000, 'Tiket bioskop', -10],
        ];
        foreach ($transactionData as [$key, $account, $category, $type, $amount, $note, $daysAgo]) {
            Transaction::updateOrCreate(
                ['user_id' => $user->id, 'idempotency_key' => 'demo-'.$key],
                [
                    'account_id' => $accounts[$account]->id,
                    'category_id' => $categories[$category]->id,
                    'type' => $type,
                    'amount' => $amount,
                    'note' => $note,
                    'date' => $today->copy()->addDays($daysAgo)->toDateString(),
                ],
            );
        }
        Transaction::updateOrCreate(
            ['user_id' => $user->id, 'idempotency_key' => 'demo-transfer'],
            [
                'account_id' => $accounts['Rekening Utama']->id,
                'related_account_id' => $accounts['Dompet Digital']->id,
                'category_id' => null,
                'type' => 'transfer',
                'amount' => 500000,
                'note' => 'Isi saldo dompet digital',
                'date' => $today->copy()->subDays(3)->toDateString(),
            ],
        );

        foreach ([['Makanan', 2500000], ['Transportasi', 900000], ['Tagihan', 1800000], ['Belanja Rumah', 1500000]] as [$category, $limit]) {
            Budget::updateOrCreate(
                ['user_id' => $user->id, 'category_id' => $categories[$category]->id, 'period' => 'monthly', 'start_date' => $monthStart],
                ['amount_limit' => $limit],
            );
        }

        $goal = Goal::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Liburan ke Jepang'],
            ['target_amount' => 30000000, 'current_amount' => 6750000, 'opening_balance' => 0, 'target_date' => $today->copy()->addMonths(10)->toDateString(), 'icon' => 'plane'],
        );
        GoalContribution::updateOrCreate(
            ['goal_id' => $goal->id, 'idempotency_key' => 'demo-trip-contribution-1'],
            ['amount' => 2500000, 'date' => $today->copy()->subDays(12)->toDateString(), 'note' => 'Tabungan awal'],
        );
        GoalContribution::updateOrCreate(
            ['goal_id' => $goal->id, 'idempotency_key' => 'demo-trip-contribution-2'],
            ['amount' => 4250000, 'date' => $today->copy()->subDays(2)->toDateString(), 'note' => 'Setoran bulan ini'],
        );
        $item = GoalItem::updateOrCreate(
            ['goal_id' => $goal->id, 'name' => 'Tiket pesawat'],
            ['quantity' => 2, 'unit_price' => 6500000, 'notes' => 'Pulang pergi Jakarta–Tokyo', 'sort_order' => 1],
        );
        $obligation = GoalObligation::updateOrCreate(
            ['goal_item_id' => $item->id, 'name' => 'Uang muka tiket'],
            ['amount' => 3000000, 'due_date' => $today->copy()->addMonths(2)->toDateString(), 'payee' => 'Maskapai', 'status' => 'planned'],
        );
        Transaction::updateOrCreate(
            ['user_id' => $user->id, 'idempotency_key' => 'demo-goal-obligation-payment'],
            [
                'account_id' => $accounts['Rekening Utama']->id,
                'category_id' => null,
                'type' => 'expense',
                'amount' => 1000000,
                'note' => 'Uang muka tiket liburan',
                'date' => $today->copy()->subDays(9)->toDateString(),
                'financial_purpose' => 'goal_obligation',
                'goal_obligation_id' => $obligation->id,
                'goal_allocation_amount' => 0,
            ],
        );
        GoalCashAllocation::updateOrCreate(
            ['user_id' => $user->id, 'idempotency_key' => 'demo-goal-cash-allocation'],
            ['goal_id' => $goal->id, 'account_id' => $accounts['Dana Tujuan']->id, 'amount' => 3000000, 'action' => 'allocate', 'note' => 'Dana khusus liburan'],
        );

        $gold = Asset::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Emas Antam'],
            ['type' => 'gold', 'valuation_mode' => 'units', 'provider' => 'Antam', 'identifier' => 'ANTAM-5G', 'storage_type' => 'physical', 'currency' => 'IDR', 'start_date' => $today->copy()->subMonths(5)->toDateString()],
        );
        $rdpu = Asset::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Reksa Dana Pasar Uang'],
            ['type' => 'rdpu', 'valuation_mode' => 'units', 'provider' => 'Demo Investasi', 'identifier' => 'RDPU-001', 'storage_type' => 'digital', 'currency' => 'IDR', 'start_date' => $today->copy()->subMonths(3)->toDateString()],
        );
        foreach ([[$gold, '1500000.00000000', 'gram'], [$rdpu, '2500000.00000000', 'IDR']] as [$asset, $price, $unit]) {
            AssetPrice::updateOrCreate(
                ['asset_id' => $asset->id, 'source' => 'demo-seed', 'captured_at' => $today->copy()->setTime(9, 0)],
                ['price' => $price, 'unit' => $unit],
            );
        }
        foreach ([['gold', 500000], ['rdpu', 1000000]] as [$assetType, $target]) {
            DB::table('asset_monthly_targets')->updateOrInsert(
                ['user_id' => $user->id, 'asset_type' => $assetType],
                ['amount' => $target, 'created_at' => now(), 'updated_at' => now()],
            );
        }
        $goldBatch = DB::table('asset_import_batches')->where('user_id', $user->id)->where('idempotency_key', 'demo-gold-import')->first();
        $goldBatchId = $goldBatch?->id ?? DB::table('asset_import_batches')->insertGetId([
            'user_id' => $user->id, 'asset_id' => $gold->id, 'reconciled_opening' => true, 'transaction_count' => 1, 'idempotency_key' => 'demo-gold-import', 'payload_hash' => hash('sha256', 'cashlog-demo-gold-import'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        AssetTransaction::updateOrCreate(
            ['user_id' => $user->id, 'idempotency_key' => 'demo-gold-opening'],
            ['asset_id' => $gold->id, 'import_batch_id' => $goldBatchId, 'type' => 'buy', 'status' => 'settled', 'origin' => 'opening', 'cash_debited' => false, 'amount' => 7500000, 'quantity' => 5, 'unit_price' => 1500000, 'price_unit_grams' => 1, 'fees' => 0, 'cost_basis' => 7500000, 'cost_basis_known' => true, 'transaction_date' => $today->copy()->subMonths(5)->toDateString(), 'confirmed_at' => $today->copy()->subMonths(5), 'settled_at' => $today->copy()->subMonths(5), 'notes' => 'Saldo pembuka demo'],
        );
        AssetTransaction::updateOrCreate(
            ['user_id' => $user->id, 'idempotency_key' => 'demo-rdpu-opening'],
            ['asset_id' => $rdpu->id, 'type' => 'buy', 'status' => 'settled', 'origin' => 'opening', 'cash_debited' => false, 'amount' => 2500000, 'quantity' => 2500000, 'unit_price' => 1, 'price_unit_grams' => 1, 'fees' => 0, 'cost_basis' => 2500000, 'cost_basis_known' => true, 'transaction_date' => $today->copy()->subMonths(3)->toDateString(), 'confirmed_at' => $today->copy()->subMonths(3), 'settled_at' => $today->copy()->subMonths(3), 'notes' => 'Saldo pembuka demo'],
        );
        GoalAssetAllocation::updateOrCreate(
            ['user_id' => $user->id, 'idempotency_key' => 'demo-goal-gold-allocation'],
            ['goal_id' => $goal->id, 'asset_id' => $gold->id, 'quantity' => 1, 'action' => 'allocate', 'note' => 'Sebagian emas untuk rencana liburan'],
        );

        $internetBill = Bill::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Internet Rumah'],
            ['amount' => 385000, 'due_date' => $today->copy()->addDays(4)->toDateString(), 'frequency' => 'monthly', 'status' => 'active', 'category' => 'Tagihan', 'note' => 'Paket internet bulanan', 'reminder_enabled' => true, 'auto_pay_enabled' => true, 'auto_pay_account_id' => $accounts['Rekening Utama']->id],
        );
        BillPayment::updateOrCreate(
            ['bill_id' => $internetBill->id, 'scheduled_for' => $today->copy()->addDays(4)->toDateString()],
            ['user_id' => $user->id, 'account_id' => $accounts['Rekening Utama']->id, 'amount' => 385000, 'status' => 'scheduled', 'message' => 'Pembayaran otomatis terjadwal'],
        );
        Bill::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Listrik'],
            ['amount' => 550000, 'due_date' => $today->copy()->addDays(8)->toDateString(), 'frequency' => 'monthly', 'status' => 'active', 'category' => 'Tagihan', 'note' => 'Estimasi tagihan listrik', 'reminder_enabled' => true, 'auto_pay_enabled' => false, 'auto_pay_account_id' => null],
        );
        Bill::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Langganan Musik'],
            ['amount' => 55000, 'due_date' => $today->copy()->subDays(1)->toDateString(), 'frequency' => 'monthly', 'status' => 'paid', 'category' => 'Hiburan', 'note' => 'Sudah dibayar', 'reminder_enabled' => false, 'auto_pay_enabled' => false, 'auto_pay_account_id' => null],
        );

        TransactionImport::updateOrCreate(
            ['user_id' => $user->id, 'file_path' => 'seed/demo-mutasi-bank.csv'],
            ['source' => 'upload', 'document_type' => 'bank_statement', 'ocr_raw_text' => 'Contoh data mutasi untuk demonstrasi.', 'parsed_data' => ['transactions' => [['date' => $today->copy()->subDays(2)->toDateString(), 'description' => 'Pembelian bahan makanan', 'amount' => 425000]]], 'confidence' => ['overall' => 0.96], 'status' => 'draft', 'processed_at' => $today->copy()->subDays(1), 'file_expires_at' => $today->copy()->addDays(30)],
        );

        DB::table('whatsapp_connections')->updateOrInsert(
            ['user_id' => $user->id],
            ['phone_number' => null, 'provider' => 'evolution', 'status' => 'pending', 'verified_at' => null, 'last_message_at' => null, 'created_at' => now(), 'updated_at' => now()],
        );
        DB::table('conversation_sessions')->updateOrInsert(
            ['user_id' => $user->id],
            ['channel' => 'whatsapp', 'state' => 'idle', 'context' => null, 'expires_at' => null, 'created_at' => now(), 'updated_at' => now()],
        );

        $this->seedSharedWorkspace($user, $partner, $accounts['Rekening Utama'], $today);
    }

    private function seedSharedWorkspace(User $user, User $partner, Account $account, $today): void
    {
        $workspace = DB::table('shared_financial_workspaces')->where('owner_user_id', $user->id)->where('name', 'Rumah Tangga Demo')->first();
        $workspaceId = $workspace?->id ?? DB::table('shared_financial_workspaces')->insertGetId([
            'owner_user_id' => $user->id, 'name' => 'Rumah Tangga Demo', 'currency' => 'IDR', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('shared_financial_workspace_members')->updateOrInsert(
            ['workspace_id' => $workspaceId, 'user_id' => $user->id], ['role' => 'owner', 'joined_at' => $today, 'updated_at' => now(), 'created_at' => now()],
        );
        DB::table('shared_financial_workspace_members')->updateOrInsert(
            ['workspace_id' => $workspaceId, 'user_id' => $partner->id], ['role' => 'member', 'joined_at' => $today, 'updated_at' => now(), 'created_at' => now()],
        );
        foreach ([[$user->id, 1800000], [$partner->id, 950000]] as [$memberId, $balance]) {
            DB::table('shared_financial_workspace_member_cash')->updateOrInsert(
                ['workspace_id' => $workspaceId, 'user_id' => $memberId], ['balance' => $balance, 'updated_at' => now(), 'created_at' => now()],
            );
        }
        $sharedGoal = DB::table('shared_financial_workspace_goals')->where('workspace_id', $workspaceId)->where('name', 'Dana Renovasi')->first();
        $sharedGoalId = $sharedGoal?->id ?? DB::table('shared_financial_workspace_goals')->insertGetId([
            'workspace_id' => $workspaceId, 'name' => 'Dana Renovasi', 'target_amount' => 25000000, 'target_date' => $today->copy()->addMonths(8)->toDateString(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('shared_financial_workspace_goal_allocations')->updateOrInsert(
            ['workspace_id' => $workspaceId, 'idempotency_key' => 'demo-shared-goal-allocation'],
            ['goal_id' => $sharedGoalId, 'member_user_id' => $user->id, 'action' => 'allocate', 'amount' => 1800000, 'allocation_date' => $today->copy()->subDays(2)->toDateString(), 'note' => 'Setoran awal renovasi', 'updated_at' => now(), 'created_at' => now()],
        );
        DB::table('shared_financial_workspace_cash_entries')->updateOrInsert(
            ['workspace_id' => $workspaceId, 'idempotency_key' => 'demo-shared-cash-entry'],
            ['member_user_id' => $user->id, 'source_account_id' => $account->id, 'personal_transaction_id' => null, 'type' => 'contribution', 'direction' => 'in', 'amount' => 1800000, 'entry_date' => $today->copy()->subDays(2)->toDateString(), 'note' => 'Setoran dana bersama', 'updated_at' => now(), 'created_at' => now()],
        );
        $sharedAsset = DB::table('shared_assets')->where('workspace_id', $workspaceId)->where('name', 'Emas Keluarga')->first();
        $sharedAssetId = $sharedAsset?->id ?? DB::table('shared_assets')->insertGetId([
            'workspace_id' => $workspaceId, 'type' => 'gold', 'name' => 'Emas Keluarga', 'provider' => 'Antam', 'latest_price_per_gram' => 1500000, 'price_captured_at' => $today->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sharedTransaction = DB::table('shared_asset_transactions')->where('workspace_id', $workspaceId)->where('idempotency_key', 'demo-shared-gold-buy')->first();
        $sharedTransactionId = $sharedTransaction?->id ?? DB::table('shared_asset_transactions')->insertGetId([
            'workspace_id' => $workspaceId, 'asset_id' => $sharedAssetId, 'recorded_by' => $user->id, 'action' => 'buy', 'amount' => 3000000, 'quantity' => 2, 'unit_price' => 1500000, 'fees' => 0, 'transaction_date' => $today->copy()->subMonths(2)->toDateString(), 'idempotency_key' => 'demo-shared-gold-buy', 'note' => 'Pembelian emas bersama', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('shared_asset_transaction_splits')->updateOrInsert(
            ['shared_asset_transaction_id' => $sharedTransactionId, 'user_id' => $user->id], ['cash_amount' => 1800000, 'quantity' => 1.2, 'cost_basis' => 1800000, 'created_at' => now(), 'updated_at' => now()],
        );
        DB::table('shared_asset_transaction_splits')->updateOrInsert(
            ['shared_asset_transaction_id' => $sharedTransactionId, 'user_id' => $partner->id], ['cash_amount' => 1200000, 'quantity' => 0.8, 'cost_basis' => 1200000, 'created_at' => now(), 'updated_at' => now()],
        );
        DB::table('shared_asset_member_holdings')->updateOrInsert(
            ['asset_id' => $sharedAssetId, 'user_id' => $user->id], ['quantity' => 1.2, 'cost_basis' => 1800000, 'created_at' => now(), 'updated_at' => now()],
        );
        DB::table('shared_asset_member_holdings')->updateOrInsert(
            ['asset_id' => $sharedAssetId, 'user_id' => $partner->id], ['quantity' => 0.8, 'cost_basis' => 1200000, 'created_at' => now(), 'updated_at' => now()],
        );

        // A pending invitation demonstrates the invite workflow without persisting a usable token.
        DB::table('shared_financial_workspace_invitations')->updateOrInsert(
            ['workspace_id' => $workspaceId, 'email' => 'guest@example.com', 'status' => 'pending'],
            ['invited_by' => $user->id, 'token_hash' => hash('sha256', 'cashlog-demo-invitation'), 'expires_at' => $today->copy()->addDays(7), 'accepted_at' => null, 'created_at' => now(), 'updated_at' => now()],
        );
    }
}
