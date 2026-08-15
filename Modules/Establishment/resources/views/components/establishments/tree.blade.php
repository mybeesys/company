@props(['establishment', 'name'])
<li id="est_{{ $establishment->id }}" data-branch-id="{{ $establishment->id }}">{{ $establishment->{$name} }}
    <ul>
        @if ($establishment->childrenTree->isNotEmpty())
            @foreach ($establishment->childrenTree as $child)
                <x-establishment::establishments.tree :establishment=$child :name=$name />
            @endforeach
        @endif
    </ul>
</li>
