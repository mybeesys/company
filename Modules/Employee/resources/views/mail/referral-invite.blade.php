<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('employee::referrals.email_subject', ['name' => $referrerName]) }}</title>
</head>
<body style="font-family: Cairo, Arial, sans-serif; background:#f8f9fc; padding:24px; color:#181c32;">
    <div style="max-width:640px; margin:0 auto; background:#fff; border:1px solid #eef1f7; border-radius:16px; padding:28px;">
        <h2 style="margin:0 0 12px;">{{ __('employee::referrals.email_heading') }}</h2>
        <p style="margin:0 0 16px; color:#7e8299;">{{ __('employee::referrals.email_intro', ['name' => $referrerName]) }}</p>
        <div style="white-space:pre-line; line-height:1.8; margin-bottom:24px;">{{ $promotionalText }}</div>
        <a href="{{ $inviteUrl }}" style="display:inline-block; background:#f5e902; color:#111; text-decoration:none; padding:12px 20px; border-radius:12px; font-weight:700;">
            {{ __('employee::referrals.email_cta') }}
        </a>
    </div>
</body>
</html>
