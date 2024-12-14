@props([
    'title' => '',
    'bodyClass' => '',
    'titleSlot' => '',
    'header' => '',
    'headerClass' => '',
    'id' => '',
    'collapsible' => false,
    'class' => '',
    'headerDiv' => true,
])

<x-cards.card class="{{ $class }}">
    @if ($headerDiv)
        <x-cards.card-header :class=$headerClass :id=$id :collapsible=$collapsible>
            <div class="card-title">
                <h2>{{ $title }}</h2>
                {{ $titleSlot }}
            </div>
            {{ $header }}
        </x-cards.card-header>
    @endif
    <div id="{{ $id }}" @class(['collapse' => $collapsible])>
        <x-cards.card-body :class="$bodyClass">
            {{ $slot }}
        </x-cards.card-body>
    </div>
</x-cards.card>
