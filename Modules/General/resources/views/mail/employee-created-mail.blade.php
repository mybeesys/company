<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
</head>

<body dir="{{ session('locale') === 'ar' ? 'rtl' : 'ltr' }}">
    <section class="max-w-2xl px-6 py-8 mx-auto bg-white dark:bg-gray-900">

        <main class="mt-8">
            {!! $body !!}
        </main>

        <footer class="mt-8">
            <p class="text-gray-500 dark:text-gray-400">
                @lang('general::general.this_email_was_sent_to'): <a href="#" class="text-blue-600 hover:underline dark:text-blue-400"
                    target="_blank">{{ $notifiable->email }}</a>.
            </p>
            <p class="mt-3 text-gray-500 dark:text-gray-400">©MyBee {{ date('Y') }}. @lang('general::general.all_rights_reserved')</p>
        </footer>
    </section>
</body>

</html>
