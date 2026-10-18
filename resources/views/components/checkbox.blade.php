@php
    use CeplokUi\DesignKit\Enums\CheckboxApplication;
    use CeplokUi\DesignKit\Enums\CheckboxPlatform;
    use CeplokUi\DesignKit\Enums\CheckboxState;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Checkbox — pilihan ganda yang bisa dicentang secara mandiri.

    Kotaknya `<input type="checkbox">` yang digambar ulang, dengan centang
    menumpang di atasnya lewat varian `peer`, jadi keyboard dan pembaca layar
    tetap bekerja seperti biasa. Keadaan tercentang memakai `checked`,
    `wire:model`, atau `x-model`, bukan prop tersendiri; di dalam Livewire
    keadaan awal `wire:model` dibaca dari propertinya, jadi HTML pertama sudah
    tercentang dengan benar.

    Atribut lain (`name`, `value`, `wire:model`, `required`, …) diteruskan ke
    `<input>`-nya; `class` dan `wire:key` ke pembungkus terluar. `label` dan
    `helper-text` bisa prop atau slot. `state="inactive"` atau `disabled`
    menonaktifkan kontrolnya. `dark-mode` memasang `data-theme="dark"` di
    pembungkus terluar.
--}}
@props([
    'label' => null,
    'helperText' => null,
    'platform' => 'default',
    'state' => 'default',
    'application' => 'default',
    'darkMode' => false,
])

@php
    $platform = Prop::oneOf(CheckboxPlatform::class, $platform, CheckboxPlatform::Default, 'checkbox', 'platform');
    $state = Prop::oneOf(CheckboxState::class, $state, CheckboxState::Default, 'checkbox', 'state');
    $application = Prop::oneOf(CheckboxApplication::class, $application, CheckboxApplication::Default, 'checkbox', 'application');
    $darkMode = Prop::bool($darkMode, false, 'checkbox', 'dark-mode');

    $id = FieldId::for($attributes, 'checkbox');
    $helperId = $id.'-helper';
    $inactive = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true) || $state === 'inactive';
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);
    $hasText = $hasLabel || $hasHelper;

    // Ukuran kotak, jarak turun agar sejajar tengah baris label, dan ukuran
    // teks label per platform. Centang tetap 10px di kedua platform.
    $platforms = [
        'default' => ['box' => 'size-4', 'offset' => 'mt-0.5', 'label' => 'text-sm'],
        'mobile' => ['box' => 'size-3.5', 'offset' => 'mt-px', 'label' => 'text-xs'],
    ];

    // Warna kotak tercentang sama di tampilan terang dan gelap.
    $accents = [
        'default' => 'checked:border-primary-700 checked:bg-primary-700 focus-visible:outline-primary-700',
        'purple' => 'checked:border-purple-500 checked:bg-purple-500 focus-visible:outline-purple-500',
    ];

    // Semuanya token semantik, jadi tema gelap cukup mengganti nilai token
    // lewat data-theme. Kotak inactive yang tercentang diisi warna teks
    // nonaktif; desain tidak meredupkan kotaknya, hanya teksnya.
    $p = $platforms[$platform];
    $tag = $hasText ? 'div' : 'span';
    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'type', 'disabled'])
        ->merge([
            'aria-describedby' => $hasHelper ? $helperId : null,
            'checked' => $attributes->has('checked') ? null : Bound::checked($attributes, 'checkbox'),
        ])
        ->class([
            'peer absolute inset-0 size-full appearance-none rounded border transition-colors',
            'focus-visible:outline-2 focus-visible:outline-offset-2',
            'disabled:cursor-not-allowed',
            'border-border-strong bg-field',
            $inactive ? 'checked:border-content-disabled checked:bg-content-disabled' : 'cursor-pointer '.$accents[$application],
        ]);
@endphp

<{{ $tag }} {{ $root->class([$hasText ? 'flex items-start gap-2' : 'inline-flex']) }}>
    <span @class(['relative inline-flex shrink-0 items-center justify-center', $p['box'], $p['offset'] => $hasText])>
        <input type="checkbox" id="{{ $id }}" @disabled($inactive) {{ $input }} />

        {{-- Centang 10px, digambar sendiri agar titik sudutnya persis seperti desain. --}}
        <svg
            class="pointer-events-none relative size-2.5 text-white opacity-0 transition-opacity peer-checked:opacity-100"
            viewBox="0 0 10 10"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M1 5 3.6 8.2 9 1.8" />
        </svg>
    </span>

    @if ($hasText)
        <div class="min-w-0">
            @if ($hasLabel)
                <label
                    for="{{ $id }}"
                    @class(['block font-bold', $p['label'], $inactive ? 'cursor-not-allowed text-content-disabled' : 'cursor-pointer text-content'])
                >{{ $label }}</label>
            @endif

            @if ($hasHelper)
                <p id="{{ $helperId }}" @class(['mt-0.5 text-xs', $inactive ? 'text-content-disabled' : 'text-content-muted'])>{{ $helperText }}</p>
            @endif
        </div>
    @endif
</{{ $tag }}>
