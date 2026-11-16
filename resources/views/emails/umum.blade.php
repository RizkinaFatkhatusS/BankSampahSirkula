<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>{{ $subjek }}</title></head>
<body style="margin:0;padding:24px;background:#f0fdf4;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;border:1px solid #bbf7d0;">
            <tr><td style="padding:20px 28px;background:#15803d;border-radius:16px 16px 0 0;color:#ffffff;font-size:18px;font-weight:bold;">♻ SIRKULA</td></tr>
            <tr><td style="padding:28px;">
                <h2 style="margin:0 0 16px;font-size:18px;color:#14532d;">{{ $subjek }}</h2>
                @foreach ($baris as $b)
                    <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">{{ $b }}</p>
                @endforeach
                @if ($tombolUrl)
                    <p style="margin:24px 0;"><a href="{{ $tombolUrl }}" style="background:#15803d;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:10px;font-size:14px;font-weight:bold;display:inline-block;">{{ $tombolTeks ?? 'Buka tautan' }}</a></p>
                    <p style="margin:0 0 12px;font-size:12px;color:#6b7280;word-break:break-all;">Jika tombol tidak berfungsi, salin tautan ini: {{ $tombolUrl }}</p>
                @endif
                @if ($catatan)
                    <p style="margin:12px 0 0;font-size:12px;color:#6b7280;">{{ $catatan }}</p>
                @endif
            </td></tr>
            <tr><td style="padding:16px 28px;border-top:1px solid #dcfce7;font-size:12px;color:#9ca3af;">Email ini dikirim otomatis oleh SIRKULA. SIRKULA tidak pernah meminta kata sandi Anda melalui email.</td></tr>
        </table>
    </td></tr></table>
</body>
</html>
