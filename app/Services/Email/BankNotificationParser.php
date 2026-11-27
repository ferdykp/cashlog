<?php

namespace App\Services\Email;

use Carbon\Carbon;

class BankNotificationParser
{
    public function identifyBank(string $from): ?string
    {
        $address = $this->senderAddress($from);
        if (! $address) {
            return null;
        }

        $domain = strtolower(substr(strrchr($address, '@') ?: '', 1));

        $senders = config('services.gmail.bank_senders', []);

        return $senders[$domain] ?? null;
    }

    public function parse(array $message): ?array
    {
        $payload = $message['payload'] ?? [];
        $headers = collect($payload['headers'] ?? [])->mapWithKeys(fn ($header) => [strtolower($header['name'] ?? '') => (string) ($header['value'] ?? '')]);
        $bankCode = $this->identifyBank((string) $headers->get('from', ''));
        if (! $bankCode) {
            return null;
        }

        $body = $this->body($payload);
        $text = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $subject = (string) $headers->get('subject', '');
        $haystack = strtolower($subject.' '.$text);

        // Ignore ordinary bank mail; keep transaction-like notifications for review even
        // when a changed template prevents amount or direction extraction.
        if (! preg_match('/transaksi|transaction|berhasil|pembayaran|pembelian|transfer|debit|kredit|mutasi|saldo/i', $haystack)) {
            return null;
        }

        $internalDate = isset($message['internalDate'])
            ? Carbon::createFromTimestampMs((int) $message['internalDate'])
            : now();

        return [
            'bank_code' => $bankCode,
            'bank_name' => match ($bankCode) {
                'blu' => 'blu',
                'seabank' => 'SeaBank',
                'mandiri' => 'Mandiri',
                default => 'BCA',
            },
            'amount' => $this->amount($text),
            'type' => $this->type($text),
            'transaction_date' => $internalDate->toDateString(),
            'note' => 'Notifikasi transaksi '.match ($bankCode) {
                'blu' => 'blu',
                'seabank' => 'SeaBank',
                'mandiri' => 'Mandiri',
                default => 'BCA',
            },
        ];
    }

    private function senderAddress(string $from): ?string
    {
        if (! preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $from, $match)) {
            return null;
        }

        return strtolower($match[0]);
    }

    private function amount(string $text): ?string
    {
        if (! preg_match('/(?:transaksi|pembayaran|pembelian|nominal|jumlah|sebesar|amount)[^\r\n]{0,70}?(?:rp\.?\s*|idr\s*)([\d.,]+)/iu', $text, $match)
            && ! preg_match('/(?:rp\.?\s*|idr\s*)([\d.,]+)/iu', $text, $match)) {
            return null;
        }

        $amount = str_replace('.', '', $match[1]);
        $amount = str_replace(',', '.', $amount);

        return is_numeric($amount) && (float) $amount > 0
            ? number_format((float) $amount, 2, '.', '')
            : null;
    }

    private function type(string $text): ?string
    {
        $incoming = preg_match('/\bkredit\b|\bcredit\b|uang masuk|dana masuk|diterima|penerimaan|transfer dari|top.?up berhasil/i', $text);
        $outgoing = preg_match('/\bdebit\b|\bdebited\b|uang keluar|dana keluar|pembayaran|pembelian|tarik tunai|transfer ke/i', $text);

        if ($incoming && ! $outgoing) {
            return 'income';
        }
        if ($outgoing && ! $incoming) {
            return 'expense';
        }

        return null;
    }

    private function body(array $part): string
    {
        $chunks = [];
        if (in_array(($part['mimeType'] ?? ''), ['text/plain', 'text/html'], true)) {
            $data = $part['body']['data'] ?? null;
            if ($data) {
                $decoded = base64_decode(strtr($data, '-_', '+/'));
                if ($decoded !== false) {
                    $chunks[] = $decoded;
                }
            }
        }
        foreach ($part['parts'] ?? [] as $child) {
            $chunks[] = $this->body($child);
        }

        return implode("\n", $chunks);
    }
}
