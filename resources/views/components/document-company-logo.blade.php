@props([
    'forPdf' => false,
    'height' => '70px',
    'class' => '',
    'alt' => '',
])
@php
    $src = \App\Support\CompanyDocumentLogo::currentSrc((bool) $forPdf);
@endphp
@if ($src)
    <img {{ $attributes->merge([
        'src' => $src,
        'alt' => $alt !== '' ? $alt : '',
        'class' => trim('document-company-logo '.$class),
        'style' => 'max-height: '.$height.'; max-width: 160px; width: auto; object-fit: contain;',
    ]) }}>
@endif
