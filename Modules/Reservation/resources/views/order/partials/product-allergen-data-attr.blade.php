@php
    $raw = $product->allergens ?? [];
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    $keys = [];
    foreach ((array) $raw as $entry) {
        if (is_array($entry) && array_key_exists('value', $entry)) {
            $v = strtolower(trim((string) $entry['value']));
            if ($v !== '') {
                $keys[] = $v;
            }
        } elseif (is_string($entry)) {
            $v = strtolower(trim($entry));
            if ($v !== '') {
                $keys[] = $v;
            }
        }
    }
    $keys = array_values(array_unique($keys));
@endphp
 data-allergen-keys='@json($keys)'
