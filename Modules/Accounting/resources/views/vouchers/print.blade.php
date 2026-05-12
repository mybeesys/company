<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
</head>

<body style="margin:0; padding:0;" dir="{{ !empty($voucherLocaleAr) ? 'rtl' : 'ltr' }}">
    @include('accounting::vouchers.partials.classic_voucher', ['forPrint' => true])
</body>

</html>
