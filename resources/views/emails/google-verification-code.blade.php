<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Kode verifikasi akun Ledger</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f5f8;font-family:Arial,Helvetica,sans-serif;color:#172238;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Kode verifikasi Ledger Anda berlaku selama 10 menit.
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f5f8;border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:560px;border-collapse:separate;border-spacing:0;">
                    <tr>
                        <td style="padding:0 0 18px 4px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" style="padding-right:11px;">
                                        <img src="{{ $logoUrl }}" width="42" height="42" alt="" style="display:block;width:42px;height:42px;border:0;border-radius:12px;">
                                    </td>
                                    <td valign="middle" style="font-size:22px;line-height:28px;font-weight:700;letter-spacing:-.5px;color:#172238;">Ledger</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0;background-color:#08796d;border-radius:18px 18px 0 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding:28px 34px 26px;color:#ffffff;">
                                        <div style="font-size:11px;line-height:16px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#d5e7a1;">Verifikasi akun</div>
                                        <h1 style="margin:9px 0 0;font-size:26px;line-height:34px;font-weight:700;letter-spacing:-.5px;color:#ffffff;">Satu langkah lagi</h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 34px 30px;background-color:#ffffff;border:1px solid #e3e9ee;border-top:0;border-radius:0 0 18px 18px;">
                            <p style="margin:0 0 12px;font-size:15px;line-height:24px;color:#344054;">Gunakan kode di bawah ini untuk memverifikasi email dan melanjutkan ke akun Ledger Anda.</p>
                            <p style="margin:0 0 18px;font-size:13px;line-height:21px;color:#667085;">Masukkan kode ini di halaman verifikasi. Jangan bagikan kode ini kepada siapa pun.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px;">
                                <tr>
                                    <td align="center" style="padding:20px 12px;background-color:#f3f7f6;border:1px solid #dce9e6;border-radius:12px;">
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:34px;line-height:42px;font-weight:700;letter-spacing:10px;color:#075e55;padding-left:10px;">{{ $code }}</div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;">
                                <tr>
                                    <td valign="top" width="28" style="padding:2px 10px 0 0;color:#08796d;font-size:16px;line-height:20px;">●</td>
                                    <td style="font-size:13px;line-height:21px;color:#475467;">Kode berlaku selama <strong style="color:#172238;">10 menit</strong> dan hanya dapat digunakan satu kali.</td>
                                </tr>
                            </table>

                            <div style="height:1px;background-color:#e8edf1;line-height:1px;font-size:1px;">&nbsp;</div>
                            <p style="margin:20px 0 0;font-size:12px;line-height:20px;color:#667085;">Jika Anda tidak meminta kode ini, abaikan email ini. Akun Anda tetap aman dan tidak ada perubahan yang dilakukan.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 18px 0;font-size:11px;line-height:18px;color:#8a96a6;">
                            Email otomatis dari Ledger · <a href="{{ $publicUrl }}" style="color:#08796d;text-decoration:none;">ledger.fdevsite.cloud</a><br>
                            Demi keamanan, jangan balas email ini atau teruskan kode verifikasi Anda.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
