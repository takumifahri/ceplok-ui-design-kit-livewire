@php
    use CeplokUi\DesignKit\Enums\ToggleApplication;
    use CeplokUi\DesignKit\Enums\TogglePlatform;
    use CeplokUi\DesignKit\Enums\ToggleState;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Toggle Button — sakelar untuk menyalakan atau mematikan satu pengaturan.

    Di dalamnya tetap `<input type="checkbox">` dengan `role="switch"`: ia
    menutupi seluruh sakelar sehingga area kliknya penuh, sementara jalur dan
    bulatannya digambar dua elemen di belakangnya lewat varian `peer`. Keadaan
    menyala memakai `checked`, `wire:model`, atau `x-model`; di dalam Livewire
    keadaan awal `wire:model` dibaca dari propertinya.

    Atribut lain (`name`, `value`, `wire:model`, …) diteruskan ke `<input>`-nya;
    `class` dan `wire:key` ke pembungkus terluar. `label` dan `helper-text`
    bisa prop atau slot. `state="inactive"` atau `disabled` menonaktifkan
    kontrolnya. `dark-mode` memasang `data-theme="dark"` di pembungkus terluar.
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
    $platform = Prop::oneOf(TogglePlatform::class, $platform, TogglePlatform::Default, 'toggle', 'platform');
    $state = Prop::oneOf(ToggleState::class, $state, ToggleState::Default, 'toggle', 'state');
    $application = Prop::oneOf(ToggleApplication::class, $application, ToggleApplication::Default, 'toggle', 'application');
    $darkMode = Prop::bool($darkMode, false, 'toggle', 'dark-mode');

    $id = FieldId::for($attributes, 'toggle');
    $helperId = $id.'-helper';
    $inactive = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true) || $state === 'inactive';
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);
    $hasText = $hasLabel || $hasHelper;

    // Ukuran jalur, bulatan, dan jarak gesernya per platform. Bulatan selalu
    // menyisakan 2px di kiri/kanan, jadi jaraknya = lebar jalur − 4 − lebar bulatan.
    $platforms = [
        'default' => ['track' => 'h-5 w-10', 'knob' => 'size-4', 'shift' => 'peer-checked:translate-x-5', 'label' => 'text-sm'],
        'mobile' => ['track' => 'h-4.5 w-9', 'knob' => 'size-3.5', 'shift' => 'peer-checked:translate-x-4.5', 'label' => 'text-xs'],
    ];

    // Warna jalur yang menyala sama di tampilan terang dan gelap.
    $accents = [
        'default' => 'peer-checked:bg-primary-700 peer-focus-visible:outline-primary-700',
        'purple' => 'peer-checked:bg-purple-500 peer-focus-visible:outline-purple-500',
    ];

    $p = $platforms[$platform];
    $tag = $hasText ? 'div' : 'span';
    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'type', 'disabled'])
        ->merge([
            'role' => 'switch',
            'aria-describedby' => $hasHelper ? $helperId : null,
            'checked' => $attributes->has('checked') ? null : Bound::checked($attributes, 'checkbox'),
        ])
        ->class([
            'peer absolute inset-0 z-10 m-0 size-full appearance-none rounded-full',
            $inactive ? 'cursor-not-allowed' : 'cursor-pointer',
        ]);
@endphp

<{{ $tag }} {{ $root->class([$hasText ? 'flex items-start gap-2' : 'inline-flex']) }}>
    <span @class(['relative inline-flex shrink-0', $p['track']])>
        <input type="checkbox" id="{{ $id }}" @disabled($inactive) {{ $input }} />

        {{--
            Jalur. Saat inactive warnanya tetap, menyala maupun mati — posisi
            bulatan yang membedakannya.
        --}}
        <span
            aria-hidden="true"
            @class([
                'block size-full rounded-full transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2',
                $inactive ? 'bg-toggle-track-inactive' : 'bg-toggle-track '.$accents[$application],
            ])
        ></span>

        {{-- `transition`, bukan `transition-transform`: di tampilan gelap warna bulatannya ikut berganti. --}}
        <span
            aria-hidden="true"
            @class([
                'pointer-events-none absolute top-0.5 left-0.5 rounded-full transition',
                $p['knob'],
                $p['shift'],
                $inactive ? 'bg-toggle-knob' : 'bg-toggle-knob peer-checked:bg-white',
            ])
        ></span>
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
                <p id="{{ $helperId }}" @class(['mt-0.5 text-xs', $inactive ? 'text-gray-400' : 'text-content-muted'])>{{ $helperText }}</p>
            @endif
        </div>
    @endif
</{{ $tag }}>
