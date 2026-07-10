<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $brandName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f4f5; padding: 32px 16px;">
<tr>
<td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
        @if (filled($logoUrl))
        <tr>
            <td align="center" style="padding: 32px 32px 16px;">
                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" width="180" style="display: block; max-width: 180px; height: auto; border: 0;">
            </td>
        </tr>
        @else
        <tr>
            <td align="center" style="padding: 32px 32px 8px;">
                <p style="margin: 0; font-size: 22px; font-weight: 700; color: #18181b; letter-spacing: -0.02em;">{{ $brandName }}</p>
            </td>
        </tr>
        @endif

        <tr>
            <td style="padding: 16px 32px 32px;">
                <div style="font-size: 16px; line-height: 1.65; color: #3f3f46;">
                    {!! $bodyHtml !!}
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding: 20px 32px 28px; border-top: 1px solid #e4e4e7; background-color: #fafafa;">
                <p style="margin: 0 0 6px; font-size: 13px; color: #71717a; text-align: center;">
                    {{ $brandName }}
                </p>
                <p style="margin: 0; font-size: 12px; color: #a1a1aa; text-align: center;">
                    <a href="{{ $siteUrl }}" style="color: #71717a; text-decoration: underline;">{{ $siteUrl }}</a>
                </p>
            </td>
        </tr>
    </table>
</td>
</tr>
</table>
</body>
</html>
