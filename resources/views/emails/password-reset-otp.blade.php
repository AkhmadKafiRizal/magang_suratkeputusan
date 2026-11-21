<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode Verifikasi Reset Kata Sandi</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-shell { width: 100% !important; }
            .email-header { padding: 25px 20px 21px !important; }
            .email-body { padding: 32px 20px 34px !important; }
            .otp-cell { width: 36px !important; height: 48px !important; font-size: 23px !important; line-height: 48px !important; }
            .otp-gap { width: 5px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#eef4fb;color:#1e293b;font-family:Arial,'Segoe UI',sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Gunakan kode verifikasi ini untuk membuat kata sandi baru. Kode berlaku selama 10 menit.
    </div>

    @php
        $embedWithoutFilename = static function (string $path, string $contentType) use ($message): string {
            $part = (new \Symfony\Component\Mime\Part\DataPart(file_get_contents($path), null, $contentType))->asInline();
            $message->getSymfonyMessage()->addPart($part);

            return 'cid:'.$part->getContentId();
        };
        $pemkabLogo = $embedWithoutFilename(public_path('images/logo-pemkab-jember.png'), 'image/png');
        $bapendaLogo = $embedWithoutFilename(public_path('images/logo-bapenda-jember.png'), 'image/png');
        $buildingPhoto = $embedWithoutFilename(public_path('images/gedung-login.webp'), 'image/webp');
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#eef4fb;">
        <tr>
            <td align="center" style="padding:36px 14px;">
                <table role="presentation" class="email-shell" width="560" cellspacing="0" cellpadding="0" border="0" align="center" style="width:560px;max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #d9e5f2;border-radius:18px;overflow:hidden;box-shadow:0 16px 40px rgba(16,59,116,.10);">
                    <tr>
                        <td style="height:6px;background:#1d63b7;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="email-header" align="center" style="padding:28px 30px 24px;text-align:center;background:#f8fbff;border-bottom:1px solid #deebf8;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
                                <tr>
                                    <td valign="middle" style="padding-right:12px;"><img src="{{ $pemkabLogo }}" width="42" height="42" alt="Logo Pemkab Jember" style="display:block;width:42px;height:42px;object-fit:contain;border:0;"></td>
                                    <td valign="middle" style="padding-right:16px;"><img src="{{ $bapendaLogo }}" width="108" height="42" alt="Logo Bapenda Jember" style="display:block;width:108px;height:42px;object-fit:contain;border:0;"></td>
                                    <td valign="middle" style="padding-left:16px;border-left:1px solid #d8e1ec;text-align:left;">
                                        <div style="margin:0;color:#103b74;font-size:19px;font-weight:800;line-height:1.3;letter-spacing:.04em;white-space:nowrap;">SIARKA</div>
                                        <div style="max-width:210px;margin-top:3px;color:#405b79;font-size:11px;font-weight:700;line-height:1.35;">Sistem Informasi Arsip dan Register Keberatan</div>
                                        <div style="max-width:210px;margin-top:3px;color:#64748b;font-size:10px;line-height:1.35;">Badan Pendapatan Daerah Kabupaten Jember</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0;background:#103b74;">
                            <img src="{{ $buildingPhoto }}" width="560" height="150" alt="Gedung Bapenda Kabupaten Jember" style="display:block;width:100%;max-width:560px;height:150px;object-fit:cover;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" align="center" style="padding:38px 46px 40px;text-align:center;">
                            <table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="padding:7px 12px;border-radius:999px;background:#e8f2ff;color:#174f95;font-size:11px;font-weight:700;line-height:1.3;letter-spacing:.06em;text-transform:uppercase;">Pemulihan Akun</td>
                                </tr>
                            </table>

                            <h1 style="margin:20px 0 10px;color:#16243a;font-size:28px;line-height:1.25;letter-spacing:-.02em;text-align:center;">Kode Verifikasi Anda</h1>
                            <p style="max-width:430px;margin:0 auto;color:#5c6b80;font-size:15px;line-height:1.7;text-align:center;">Masukkan kode berikut pada halaman verifikasi untuk membuat kata sandi baru.</p>

                            <table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0" style="margin:28px auto;">
                                <tr>
                                    @foreach(str_split($otp) as $digit)
                                        <td class="otp-cell" width="46" height="56" align="center" valign="middle" style="width:46px;height:56px;border:1px solid #bfd4ed;border-radius:10px;background:#f4f8fd;color:#103b74;font-size:28px;font-weight:800;line-height:56px;text-align:center;">{{ $digit }}</td>
                                        @if(! $loop->last)<td class="otp-gap" width="7" style="width:7px;font-size:0;line-height:0;">&nbsp;</td>@endif
                                    @endforeach
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" align="center" style="width:100%;margin:0 auto;border-radius:12px;background:#eef6ff;">
                                <tr>
                                    <td align="center" style="padding:15px 17px;color:#315b88;font-size:14px;line-height:1.55;text-align:center;">
                                        <strong style="color:#174f95;">Kode berlaku selama 10 menit</strong><br>
                                        dan hanya dapat digunakan satu kali.
                                    </td>
                                </tr>
                            </table>

                            <p style="max-width:430px;margin:24px auto 0;color:#6b778a;font-size:13px;line-height:1.65;text-align:center;">Jangan berikan kode ini kepada siapa pun. Jika Anda tidak meminta perubahan kata sandi, abaikan email ini.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 24px;background:#103b74;color:#dceafb;font-size:12px;line-height:1.6;text-align:center;">
                            <strong style="color:#ffffff;">SIARKA Bapenda Jember</strong><br>
                            Sistem Informasi Arsip dan Register Keberatan
                        </td>
                    </tr>
                </table>
                <p style="margin:16px auto 0;color:#8290a3;font-size:11px;line-height:1.5;text-align:center;">Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
            </td>
        </tr>
    </table>
</body>
</html>
