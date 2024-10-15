@props([
    'title' => '',
    'bodyClass' => '',
    'titleSlot' => '',
    'header' => '',
    'headerClass' => '',
    'id' => '',
    'collapsible' => false,
    'class' => '',
])

<div @class(['card card-flush py-4', $class])>
    <x-cards.card-header :class=$headerClass :id=$id :collapsible=$collapsible>
        <div class="card-title">
            <h2>{{ $title }}</h2>
            {{ $titleSlot }}
        </div>
        {{ $header }}
    </x-employee::card-header>
    <div id="{{ $id }}" @class(['collapse' => $collapsible])>
        <x-cards.card-body :class="$bodyClass">
            {{ $slot }}
        </x-employee::card-body>
    </div>
</div>
