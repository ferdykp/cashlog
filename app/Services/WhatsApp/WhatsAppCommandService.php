<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Support\DecimalMath;
use Illuminate\Support\Str;

class WhatsAppCommandService
{
    public function respond(User $user, string $input): ?string
    {
        $command = Str::lower(trim(preg_replace('/\s+/u', ' ', $input)));
        $command = $this->correctSimpleCommandTypo($command);
        if (in_array($command, ['bantuan', 'help', 'panduan', 'halo ledger', 'menu', 'commands'], true)) {
            return $this->help();
        }
        $categoryType = match ($command) {
            'kategori pengeluaran' => 'expense',
            'kategori pemasukan' => 'income',
            default => null,
        };
        if ($command === 'kategori' || $categoryType !== null) {
            $query = $user->categories()
                ->whereIn('type', $categoryType ? [$categoryType] : ['expense', 'income'])
                ->orderBy('type')
                ->orderBy('name');
            $categories = $query->get(['name', 'type']);
            $kindLabel = $categoryType === 'expense' ? 'pengeluaran' : 'pemasukan';

            if ($categories->isEmpty()) {
                $description = $categoryType ? 'Belum ada kategori '.$kindLabel.'.' : 'Belum ada kategori pemasukan atau pengeluaran.';

                return WhatsAppMessageFormat::message('Kategori '.($categoryType ? $kindLabel : 'tersedia'), $description, 'Tambahkan kategori di aplikasi Ledger, lalu gunakan *kategori: Nama Kategori* saat mencatat transaksi.');
            }

            $expense = $categories->where('type', 'expense')
                ->map(fn ($category) => '• '.WhatsAppMessageFormat::text($category->name))
                ->implode("\n");
            $income = $categories->where('type', 'income')
                ->map(fn ($category) => '• '.WhatsAppMessageFormat::text($category->name))
                ->implode("\n");

            $sections = [];
            if ($categoryType === null || $categoryType === 'expense') {
                $sections[] = '*Pengeluaran*'.($expense !== '' ? "\n".$expense : "\nBelum ada kategori.");
            }
            if ($categoryType === null || $categoryType === 'income') {
                $sections[] = '*Pemasukan*'.($income !== '' ? "\n".$income : "\nBelum ada kategori.");
            }
            if ($categoryType === null) {
                $sections[] = 'Transfer antar-dompet tidak memakai kategori.';
            }
            $sections[] = 'Gunakan nama persis dari daftar dan letakkan kategori di akhir. Catatan boleh ditulis sebelumnya: *makan 35rb gopay catatan: makan siang kategori: [nama kategori]*.';

            return WhatsAppMessageFormat::message('Kategori '.($categoryType ? $kindLabel : 'tersedia'), ...$sections);
        }
        $accounts = fn () => $user->accounts()->where('is_archived', false)->orderBy('name');
        if ($command === 'dompet') {
            $query = $accounts();
            $count = (clone $query)->count();
            if (! $count) {
                return WhatsAppMessageFormat::message('Dompet Anda', 'Belum ada dompet aktif.', 'Tambahkan dompet melalui menu *Akun & Dompet* di aplikasi Ledger.');
            }
            $total = (clone $query)->sum('balance');
            $lines = $query->limit(20)->get()->map(fn ($account) => '💳 *'.WhatsAppMessageFormat::text($account->name)."*\n".WhatsAppMessageFormat::money($account->balance))->implode("\n\n");

            return WhatsAppMessageFormat::message('Dompet Anda', $lines,
                '*Total saldo aktif: '.WhatsAppMessageFormat::money($total).'*',
                $count > 20 ? 'Menampilkan 20 dari '.$count.' dompet. Lihat seluruh daftar di aplikasi Ledger.' : '',
                'Ketik *saldo [nama dompet]* untuk melihat saldo satu dompet.');
        }
        if (preg_match('/^saldo(?:\s+(.+))?$/u', $command, $matches)) {
            $name = trim($matches[1] ?? '');
            if ($name === '') {
                return WhatsAppMessageFormat::message('Cek saldo', 'Sertakan nama dompet yang ingin dilihat.', "Contoh: *saldo bca*\nKetik *dompet* untuk melihat nama dompet Anda.");
            }
            $account = $accounts()->get(['name', 'balance'])->first(fn ($item) => Str::lower(trim(preg_replace('/\s+/u', ' ', $item->name))) === $name);
            if (! $account) {
                return WhatsAppMessageFormat::message('Dompet tidak ditemukan', 'Nama “'.WhatsAppMessageFormat::text($name).'” tidak cocok dengan dompet aktif Anda.', 'Ketik *dompet*, lalu gunakan nama yang tertera.');
            }

            return WhatsAppMessageFormat::message('Saldo '.WhatsAppMessageFormat::text($account->name), '💳 *'.WhatsAppMessageFormat::money($account->balance).'*', 'Dihitung dari transaksi yang tercatat di Ledger.');
        }
        if (preg_match('/^ringkasan\s+(hari ini|bulan ini)$/u', $command, $matches)) {
            $today = WhatsAppMessageFormat::today();
            $daily = $matches[1] === 'hari ini';
            $start = $daily ? $today : $today->startOfMonth();
            $end = $daily ? $today : $today->endOfMonth();
            $totals = $user->transactions()->whereNull('financial_purpose')->whereIn('type', ['income', 'expense'])
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('type, SUM(amount) as total, COUNT(*) as count')->groupBy('type')->get()->keyBy('type');
            $income = DecimalMath::add((string) ($totals->get('income')?->total ?? '0'), '0.00');
            $expense = DecimalMath::add((string) ($totals->get('expense')?->total ?? '0'), '0.00');
            $count = $totals->sum('count');

            return WhatsAppMessageFormat::message('Ringkasan '.$matches[1],
                $daily ? WhatsAppMessageFormat::date($today->toDateString()) : $today->locale('id')->translatedFormat('F Y'),
                '📈 Pemasukan: *'.WhatsAppMessageFormat::money($income)."*\n📉 Pengeluaran: *".WhatsAppMessageFormat::money($expense)."*\n➖ Selisih: *".WhatsAppMessageFormat::money(DecimalMath::subtract($income, $expense)).'*',
                $count ? $count.' transaksi tercatat.' : 'Belum ada transaksi pada periode ini.',
                '_Transfer antar-dompet tidak dihitung. Selisih periode bukan saldo dompet._');
        }
        if (in_array($command, ['riwayat', 'riwayat transaksi', 'transaksi terakhir', '5 transaksi terakhir'], true)) {
            $transactions = $user->transactions()->with(['account:id,name', 'relatedAccount:id,name'])->orderByDesc('date')->orderByDesc('id')->limit(5)->get();
            if ($transactions->isEmpty()) {
                return WhatsAppMessageFormat::message('Transaksi terakhir', 'Belum ada transaksi yang tercatat.', 'Ketik *bantuan* untuk mulai mencatat.');
            }
            $lines = $transactions->map(function ($tx, $index) {
                $type = match ($tx->type) {
                    'income' => 'Pemasukan', 'transfer' => 'Transfer', default => 'Pengeluaran'
                };
                $wallet = WhatsAppMessageFormat::text($tx->account?->name ?? 'Dompet tidak tersedia');
                if ($tx->type === 'transfer') {
                    $wallet .= ' → '.WhatsAppMessageFormat::text($tx->relatedAccount?->name ?? 'Dompet tidak tersedia');
                }

                return ($index + 1).'. *'.$type.' · '.WhatsAppMessageFormat::money($tx->amount)."*\n📅 ".WhatsAppMessageFormat::date($tx->date->toDateString()).' · '.$wallet
                    .($tx->note ? "\n".WhatsAppMessageFormat::text($tx->note, 100) : '');
            })->implode("\n\n");

            return WhatsAppMessageFormat::message('Transaksi terakhir', $lines, 'Lihat atau ubah detail melalui menu *Transaksi* di aplikasi Ledger.');
        }

        return null;
    }

    private function correctSimpleCommandTypo(string $command): string
    {
        if (str_contains($command, ' ')) {
            return $command;
        }

        $targets = ['bantuan', 'dompet', 'kategori', 'riwayat'];
        $matches = array_values(array_filter(
            $targets,
            fn ($target) => levenshtein($command, $target) === 1
        ));

        return count($matches) === 1 ? $matches[0] : $command;
    }

    public function help(): string
    {
        return WhatsAppMessageFormat::message('Panduan WhatsApp', 'Catat transaksi, cek saldo, dan pantau ringkasan keuangan langsung lewat chat.',
            "*🧾 Catat transaksi*\n• Pengeluaran: makan 35rb gopay\n• Dengan kategori: makan 35rb gopay kategori: [nama kategori]\n• Dengan catatan dan tanggal: makan 35rb gopay catatan: makan siang kategori: [nama kategori] tanggal: 24-11-2026\n• Pemasukan: gaji 5jt masuk bca\n• Transfer: transfer 100rb bca ke gopay",
            "*📊 Pantau keuangan*\n• dompet\n• saldo bca\n• kategori / kategori pengeluaran / kategori pemasukan\n• ringkasan hari ini\n• ringkasan bulan ini\n• riwayat",
            "*✅ Periksa sebelum menyimpan*\nSetiap transaksi muncul sebagai draf. Balas *1* untuk simpan atau *3* untuk batalkan. Draf aktif 30 menit.\nUntuk pemasukan/pengeluaran, AI dapat membantu memahami typo ringan pada deskripsi, dompet, atau kategori jika tersedia. Transfer diproses dengan aturan tetap. Pastikan detail draf sudah benar.\nUntuk mengubah detail, batalkan draf lalu kirim ulang transaksi.",
            'Ganti [nama kategori] dengan nama persis dari perintah *kategori*. Catatan opsional memakai *catatan: ...*. Tanggal opsional: *tanggal: hari ini*, *tanggal: kemarin*, *tanggal: 24-11-2026*, atau *tanggal: 2026-11-24*. Tanpa tanggal, transaksi memakai hari ini. Nominal: *50rb*, *50k*, *50000*, atau *1,5jt*.');
    }
}
