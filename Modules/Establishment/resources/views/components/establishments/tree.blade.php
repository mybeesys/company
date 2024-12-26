@props(['establishment', 'name'])
<li id="est_{{ $establishment->id }}">{{ $establishment->{$name} }}
    <ul>
        @if ($establishment->children()->exists())
            @foreach ($establishment->children as $child)
                <x-establishment::establishments.tree :establishment=$child :name=$name />
            @endforeach
        @endif
    </ul>
</li>
