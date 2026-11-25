<?php

namespace App\Services\WhatsApp;

use Carbon\CarbonImmutable;

final class WhatsAppMessageFormat
{
    public static function message(string $title, string ...$sections): string
    {
        return '*Ledger · '.$title."*\n\n".implode("\n\n", array_filter($sections, fn ($section) => $section !== ''));
    }

    public static function text(?string $value, int $limit = 180): string
    {
        // User content stays on one line and cannot introduce WhatsApp formatting.
        $value = preg_replace('/[\p{Cc}\p{Cf}\s]+/u', ' ', $value ?? '');
        $value = str_replace(['*', '_', '~', '`'], '', $value);

        return mb_strimwidth(trim($value), 0, $limit, '…');
    }

    public static function money(int|string|null $amount): string
    {
        $number = (string) ($amount ?? '0');
        $negative = str_starts_with($number, '-');
        $number = ltrim($number, '+-');
        [$integer, $fraction] = array_pad(explode('.', $number, 2), 2, '');
        $integer = ltrim($integer, '0') ?: '0';
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);
        $formatted = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer);

        return ($negative ? '−' : '').'Rp'.$formatted.(trim($fraction, '0') !== '' ? ','.$fraction : '');
    }

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('services.whatsapp.timezone', 'Asia/Jakarta'));
    }

    public static function date(string $date): string
    {
        return CarbonImmutable::parse($date)->locale('id')->translatedFormat('j M Y');
    }

    public static function details(array $data): string
    {
        [$icon, $type] = match ($data['type']) {
            'income' => ['💰', 'Pemasukan'],
            'transfer' => ['🔄', 'Transfer'],
            default => ['🧾', 'Pengeluaran'],
        };
        $wallet = self::text($data['account_name'] ?? 'Dompet tidak tersedia');
        if ($data['type'] === 'transfer') {
            $wallet .= ' → '.self::text($data['related_account_name'] ?? 'Dompet tidak tersedia');
        }
        $lines = [$icon.' *'.$type.' · '.self::money($data['amount']).'*'];
        if ($data['type'] === 'transfer') {
            $lines[] = '💳 '.$wallet;
        } else {
            $lines[] = '💳 '.$wallet;
            $lines[] = '🏷️ '.self::text($data['category_name'] ?? 'Tanpa kategori');
        }
        $lines[] = '📅 '.self::date($data['date']);
        if (! empty($data['note'])) {
            $lines[] = '📝 '.self::text($data['note']);
        }

        return implode("\n", $lines);
    }

    public static function actions(): string
    {
        return "Balas *1* untuk simpan · *3* untuk batalkan\n_Draf aktif selama 30 menit._";
    }

    public static function welcome(): string
    {
        return self::message('WhatsApp terhubung', '✅ Nomor Anda sudah terverifikasi. Mulai catat transaksi lewat chat.',
            "*Coba kirim*\n• makan 35rb gopay\n• gaji 5jt masuk bca\n• transfer 100rb bca ke gopay",
            'Setiap transaksi akan muncul sebagai draf untuk Anda periksa sebelum disimpan.',
            'Balas *1* untuk simpan atau *3* untuk batalkan. Ketik *bantuan* untuk melihat semua perintah.');
    }
}
