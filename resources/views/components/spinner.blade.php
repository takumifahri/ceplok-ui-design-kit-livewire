@php
    use CeplokUi\DesignKit\Enums\SpinnerSize;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Spinner — indikator memuat. Bawaannya diumumkan pembaca layar sebagai
    `role="status"` berlabel "Loading"; ganti lewat `aria-label`. Bila ada
    teks pendamping yang sudah menjelaskan, pasang `aria-hidden="true"`:
    role dan label-nya ikut dilepas supaya tidak diumumkan dua kali.
--}}
@props([
    'size' => 'default',
])

@php
    $size = Prop::oneOf(SpinnerSize::class, $size, SpinnerSize::Default, 'spinner', 'size');
    $sizes = [
        'default' => 'size-[50px]',
        'large' => 'size-[100px]',
    ];
    $hidden = in_array($attributes->get('aria-hidden'), [true, 'true'], true);
    // `:aria-hidden="true"` dicetak Blade sebagai aria-hidden="aria-hidden"
    // (aturan atribut boolean HTML). Nilai itu tidak sah untuk ARIA dan dibaca
    // pembaca layar sebagai tidak tersembunyi, jadi ditulis ulang menjadi
    // "true", sama seperti React.
    $attributes = $hidden
        ? $attributes->except(['role', 'aria-label', 'aria-hidden'])->merge(['aria-hidden' => 'true'])
        : $attributes->merge(['role' => 'status', 'aria-label' => 'Loading']);
@endphp

<svg viewBox="0 0 50 50" fill="none" {{ $attributes->class(['shrink-0 animate-spin motion-reduce:animate-none', $sizes[$size]]) }}>
    <circle cx="25" cy="25" r="20" class="stroke-gray-200" stroke-width="4" />
    <circle
        cx="25"
        cy="25"
        r="20"
        class="stroke-primary-600"
        stroke-width="4"
        stroke-linecap="round"
        pathLength="100"
        stroke-dasharray="25 75"
    />
</svg>
