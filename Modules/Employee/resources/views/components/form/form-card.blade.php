@props(['title', 'bodyClass' => '', 'body' => '', 'header' => ''])

<div class="card card-flush py-4">
    <x-employee::card-header>
        <div class="card-title">
            <h2>{{ $title }}</h2>
            {{ $header }}
        </div>
    </x-employee::card-header>
    <x-employee::card-body :class="$bodyClass">
        {{ $slot }}
    </x-employee::card-body>
</div>
