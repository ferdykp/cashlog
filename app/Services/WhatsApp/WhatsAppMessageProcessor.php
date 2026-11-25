<?php

namespace App\Services\WhatsApp;

use App\Models\ConversationSession;
use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Services\AI\GeminiParser;
use App\Services\QuickAdd\QuickAddParser;
use App\Services\TransactionService;
use App\Support\TransactionRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WhatsAppMessageProcessor
{
    private const CONFIRM = [
        '1',
        'confirm',
        'konfirmasi',
        'ya',
        'yes',
    ];

    private const CANCEL = [
        '3',
        'cancel',
        'batal',
    ];

    public function __construct(
        private QuickAddParser $parser,
        private GeminiParser $aiParser,
        private TransactionService $transactions,
        private WhatsAppCommandService $commands,
    ) {}

    public function handle(string $phone, string $text): string
    {
        return DB::transaction(function () use ($phone, $text) {
            $connection = WhatsAppConnection::where(
                'phone_number',
                $phone
            )->where('status', 'connected')->first();

            if (! $connection || preg_match('/^LINK\s+/i', trim($text))) {
                return WhatsAppMessageFormat::message('Hubungkan WhatsApp', 'Nomor ini belum terhubung ke Ledger.', 'Buka *Pengaturan → WhatsApp* di aplikasi, lalu verifikasi nomor dengan kode OTP untuk mulai mencatat transaksi.');
            }

            $user = User::whereKey($connection->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $connection = WhatsAppConnection::whereKey($connection->id)
                ->where('phone_number', $phone)
                ->where('status', 'connected')
                ->lockForUpdate()
                ->first();

            if (! $connection) {
                return WhatsAppMessageFormat::message('Koneksi terputus', 'WhatsApp belum terhubung ke Ledger.', 'Verifikasi ulang nomor melalui *Pengaturan → WhatsApp*, lalu coba lagi.');
            }

            $connection->update([
                'last_message_at' => now(),
            ]);

            $session = ConversationSession::firstOrCreate(
                ['user_id' => $user->id],
                ['channel' => 'whatsapp', 'state' => 'idle']
            );

            $session = ConversationSession::whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->expires_at?->isPast()) {
                $this->clear($session);
            }

            $command = Str::lower(trim($text));

            $informationalReply = $this->commands->respond(
                $user,
                $command
            );

            if ($informationalReply !== null) {
                return $informationalReply;
            }

            if ($session->state === 'waiting_confirmation') {
                return $this->confirmation(
                    $user,
                    $session,
                    $command
                );
            }

            if (in_array(
                $command,
                [...self::CONFIRM, ...self::CANCEL],
                true
            )) {
                return WhatsAppMessageFormat::message('Belum ada transaksi untuk dikonfirmasi', 'Tidak ada transaksi yang menunggu konfirmasi saat ini.', 'Kirim transaksi baru, misalnya *makan 35rb gopay*. Ketik *bantuan* untuk melihat contoh lainnya.');
            }

            return $this->draft(
                $user,
                $session,
                trim($text)
            );
        }, 3);
    }

    private function draft(
        User $user,
        ConversationSession $session,
        string $text
    ): string {
        $accounts = $user->accounts()
            ->where('is_archived', false)
            ->get();

        $draft = $this->parser->parse($user, $text);
        if ($draft['ambiguous_account'] ?? false) {
            return WhatsAppMessageFormat::message('Periksa nama dompet', 'Pesan menyebut lebih dari satu dompet, jadi transaksinya belum dibuat.', 'Pemasukan atau pengeluaran memakai satu dompet. Untuk memindahkan saldo, tulis *transfer 50rb bca ke gopay*.');
        }
        if (! empty($draft['date_error'])) {
            return WhatsAppMessageFormat::message('Format tanggal belum dikenali', 'Tanggal “'.WhatsAppMessageFormat::text($draft['date_error']).'” belum bisa dibaca.', 'Gunakan *tanggal: hari ini*, *tanggal: kemarin*, *tanggal: 24-11-2026*, atau *tanggal: 2026-11-24*.');
        }
        if (empty($draft['amount']) && preg_match('/\d/u', $text)) {
            return WhatsAppMessageFormat::message('Nominal belum terbaca', 'Tulis satu nominal positif agar transaksi bisa dicatat.', 'Contoh: *35000*, *35rb*, atau *50.000,50*. Kirim ulang transaksi; saldo belum berubah.');
        }

        /*
         * Deteksi pemasukan eksplisit.
         *
         * Contoh:
         * - uang sate 141rb masuk ke bca
         * - gaji 5jt masuk bca
         * - terima transfer 200rb dari teman ke bca
         *
         * Hanya ubah jenis transaksi apabila kalimat
         * jelas menunjukkan penerimaan uang.
         */
        if ($this->isIncomingMoney($text) && ($draft['type'] !== 'transfer'
            || (empty($draft['related_account_id']) && preg_match('/\b(?:terima\s+transfer|ditransfer)\b/iu', $text)))) {
            $draft['type'] = 'income';

            // Kategori pengeluaran tidak boleh dipakai
            // untuk transaksi pemasukan.
            if (! empty($draft['category_id'])) {
                $category = $user->categories()
                    ->whereKey($draft['category_id'])
                    ->where('type', 'income')
                    ->first();

                if (! $category) {
                    $draft['category_id'] = null;
                    $draft['category_name'] = null;
                }
            }

            $account = $this->findIncomingAccount(
                $accounts,
                $text
            );

            if ($account) {
                $draft['account_id'] = $account->id;
            }

            // Jika field inti sudah lengkap, tidak perlu
            // memanggil Gemini hanya karena confidence rendah.
            if (
                ! empty($draft['amount'])
                && ! empty($draft['account_id'])
            ) {
                $draft['confidence'] = 1;
            }

            $draft['source'] = 'incoming-rule';
        }

        if (($draft['category_requested'] ?? null) !== null && $draft['type'] === 'transfer') {
            return WhatsAppMessageFormat::message('Kategori tidak diperlukan', 'Transfer antar-dompet tidak memakai kategori.', 'Contoh: *transfer 50rb bca ke gopay*.');
        }

        /*
         * Gemini handles missing fields, unclassified descriptions, and
         * explicit names that did not match. It selects only canonical
         * values from the user's lists.
         *
         * Jangan mengubah arah transfer menggunakan AI.
         */
        $needsAi = $draft['type'] !== 'transfer'
            && (
                empty($draft['amount'])
                || empty($draft['account_id'])
                || (empty($draft['category_id']) && $user->categories()->where('type', $draft['type'])->exists())
                || (
                    ($draft['confidence'] ?? 0) < 0.75
                    && ! $this->isIncomingMoney($text)
                )
            );

        if ($needsAi) {
            $draft = $this->mergeAi(
                $user,
                $draft,
                $text
            );
        }

        if (($draft['category_requested'] ?? null) !== null && empty($draft['category_id'])) {
            $typeLabel = $draft['type'] === 'income' ? 'pemasukan' : 'pengeluaran';
            $categoryNames = $user->categories()
                ->where('type', $draft['type'])
                ->orderBy('name')
                ->pluck('name')
                ->map(fn ($name) => WhatsAppMessageFormat::text($name))
                ->implode(', ');

            return WhatsAppMessageFormat::message('Kategori belum cocok', '“'.WhatsAppMessageFormat::text($draft['category_requested']).'” tidak ditemukan untuk '.$typeLabel.'.',
                $categoryNames !== '' ? 'Pilih salah satu kategori: '.$categoryNames.'.' : 'Belum ada kategori untuk jenis transaksi ini. Tambahkan kategori di aplikasi Ledger.',
                'Kirim ulang dengan format *kategori: [nama persis]*. Ketik *kategori* untuk melihat daftar kategori.');
        }

        if (
            $draft['type'] !== 'transfer'
            && empty($draft['account_id'])
            && preg_match(
                '/\b\d[\d.,]*\s*(?:rb|ribu|k|jt|juta)?\s+'
                    .'(?:(?:masuk|dari|pakai|via|ke|di)\s+)?'
                    .'([\pL][\pL\pN ._-]{0,39})$/iu',
                $text,
                $match
            )
        ) {
            return WhatsAppMessageFormat::message('Dompet belum ditemukan', '“'.WhatsAppMessageFormat::text(trim($match[1])).'” belum cocok dengan nama dompet terdaftar.', 'Ketik *dompet* untuk melihat daftar. Jika tadi ada typo, kirim ulang dengan ejaan yang benar.');
        }

        $payload = $this->payload($draft);

        $validator = Validator::make(
            $payload,
            TransactionRules::forUser(
                $user,
                $payload['type']
            )
        );

        if ($validator->fails()) {
            return WhatsAppMessageFormat::message('Detail transaksi perlu diperiksa', 'Nominal, tanggal, kategori, atau dompet belum valid. Belum ada transaksi yang disimpan.', "*Contoh format*\n• makan 35rb gopay\n• gaji 5jt masuk bca\n• transfer 50rb bca ke gopay", 'Ketik *dompet* untuk melihat nama dompet aktif.');
        }

        $account = $accounts->firstWhere(
            'id',
            $payload['account_id']
        );

        $target = $accounts->firstWhere(
            'id',
            $payload['to_account_id']
        );

        if (
            ! $account
            || (
                $payload['type'] === 'transfer'
                && ! $target
            )
        ) {
            return WhatsAppMessageFormat::message('Dompet tidak tersedia', 'Dompet pada pesan ini sudah tidak aktif di Ledger.', 'Ketik *dompet* untuk melihat dompet aktif, lalu kirim ulang transaksi.');
        }

        $draft['category_name'] = $payload['category_id'] ? $user->categories()->find($payload['category_id'])?->name : null;
        $draft['account_name'] = $account->name;
        $draft['related_account_name'] = $target?->name;

        $session->update([
            'state' => 'waiting_confirmation',
            'context' => $draft,
            'expires_at' => now()->addMinutes(30),
        ]);

        return WhatsAppMessageFormat::message('Cek transaksi', 'Periksa ringkasan ini sebelum disimpan.', WhatsAppMessageFormat::details($draft), WhatsAppMessageFormat::actions());
    }

    /*
     * Membedakan penerimaan uang dari transfer
     * antar-dompet.
     */
    private function isIncomingMoney(string $text): bool
    {
        $text = Str::lower(trim($text));

        // Transfer eksplisit dari dompet sendiri
        // tidak boleh diubah menjadi pemasukan.
        if (preg_match(
            '/\b(?:transfer|pindah(?:kan)?\s+saldo)\s+'
                .'\d[\d.,]*\s*(?:rb|ribu|k|jt|juta)?\s+'
                .'[\pL\pN_-]+\s+ke\s+[\pL\pN_-]+/iu',
            $text
        )) {
            return false;
        }

        return (bool) preg_match(
            '/\b(?:'
                .'masuk(?:\s+ke)?'
                .'|pemasukan'
                .'|pendapatan'
                .'|terima\s+transfer'
                .'|ditransfer'
                .'|uang\s+masuk'
                .'|gaji'
                .')\b/iu',
            $text
        );
    }

    /*
     * Cari dompet tujuan berdasarkan nama dompet
     * aktif yang benar-benar dimiliki user.
     */
    private function findIncomingAccount(
        $accounts,
        string $text
    ) {
        $normalized = Str::lower(trim($text));

        // Utamakan nama terpanjang agar nama dompet
        // yang mirip tidak salah terpilih.
        $sorted = $accounts->sortByDesc(
            fn ($account) => mb_strlen($account->name)
        );

        foreach ($sorted as $account) {
            $name = Str::lower(trim($account->name));

            if ($name === '') {
                continue;
            }

            $escaped = preg_quote($name, '/');

            if (preg_match(
                '/(?:^|[^\pL\pN])'
                    .$escaped
                    .'(?=$|[^\pL\pN])/iu',
                $normalized
            )) {
                return $account;
            }
        }

        return null;
    }

    private function confirmation(
        User $user,
        ConversationSession $session,
        string $command
    ): string {
        if (in_array($command, self::CANCEL, true)) {
            $this->clear($session);

            return WhatsAppMessageFormat::message('Transaksi dibatalkan', 'Draf dihapus. Tidak ada transaksi yang disimpan dan saldo tetap.', 'Kirim transaksi baru kapan saja untuk mulai lagi.');
        }

        if (! in_array($command, self::CONFIRM, true)) {
            return WhatsAppMessageFormat::message('Draf masih menunggu', 'Periksa transaksi yang terakhir dikirim.', 'Balas *1* untuk simpan atau *3* untuk batalkan. Untuk mengubah detail, batalkan lalu kirim ulang transaksi.');
        }

        $payload = $this->payload(
            $session->context ?? []
        );

        $validator = Validator::make(
            $payload,
            TransactionRules::forUser(
                $user,
                $payload['type']
            )
        );

        $ids = array_filter([
            $payload['account_id'],
            $payload['to_account_id'],
        ]);

        $active = $user->accounts()
            ->whereIn('id', $ids)
            ->where('is_archived', false)
            ->count();

        if (
            $validator->fails()
            || $active !== count(array_unique($ids))
        ) {
            $this->clear($session);

            return WhatsAppMessageFormat::message('Draf perlu diperbarui', 'Detail transaksi tidak valid atau dompet telah berubah. Saldo belum diubah.', 'Kirim ulang transaksi dengan detail terbaru. Ketik *dompet* untuk melihat dompet aktif.');
        }

        $transaction = $this->transactions
            ->createTransaction(
                $user,
                $validator->validated()
            );

        $this->clear($session);

        return WhatsAppMessageFormat::message('Transaksi tersimpan',
            WhatsAppMessageFormat::details([
                'type' => $transaction->type, 'amount' => $transaction->amount,
                'account_name' => $transaction->account?->name,
                'related_account_name' => $transaction->relatedAccount?->name,
                'category_name' => $transaction->category?->name,
                'date' => $transaction->date->toDateString(), 'note' => $transaction->note,
            ]),
            'Saldo dompet sudah diperbarui. Ketik *dompet* untuk melihat saldo terbaru.');
    }

    private function payload(array $draft): array
    {
        return [
            'type' => $draft['type'] ?? null,
            'account_id' => $draft['account_id'] ?? null,
            'to_account_id' => ($draft['type'] ?? null) === 'transfer'
                ? ($draft['related_account_id'] ?? null)
                : null,
            'category_id' => $draft['category_id'] ?? null,
            'amount' => $draft['amount'] ?? null,
            'date' => $draft['date'] ?? WhatsAppMessageFormat::today()->toDateString(),
            'note' => $draft['note'] ?? null,
        ];
    }

    private function clear(ConversationSession $session): void
    {
        $session->update([
            'state' => 'idle',
            'context' => null,
            'expires_at' => null,
        ]);
    }

    private function mergeAi(
        User $user,
        array $draft,
        string $text
    ): array {
        $ai = $this->aiParser->parse($user, $text);

        if (! $ai) {
            return $draft;
        }

        if (empty($draft['amount'])) {
            $draft['amount'] = $ai['amount'] ?? null;
        }

        if (
            empty($draft['account_id'])
            && ! empty($ai['account_name'])
        ) {
            $account = $user->accounts()
                ->where('is_archived', false)
                ->whereRaw(
                    'LOWER(name) = ?',
                    [Str::lower($ai['account_name'])]
                )
                ->first();

            $draft['account_id'] = $account?->id;
        }

        // Jangan izinkan AI mengubah jenis transaksi
        // yang sudah ditentukan oleh parser lokal.
        if (
            empty($draft['category_id'])
            && ! empty($ai['category_name'])
        ) {
            $category = $user->categories()
                ->where('type', $draft['type'])
                ->whereRaw(
                    'LOWER(name) = ?',
                    [Str::lower($ai['category_name'])]
                )
                ->first();

            $draft['category_id'] = $category?->id;
            $draft['category_name'] = $category?->name;
        }

        $draft['source'] = 'rule+gemini';

        return $draft;
    }
}
