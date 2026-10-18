@php
    use CeplokUi\DesignKit\Enums\RadioApplication;
    use CeplokUi\DesignKit\Enums\RadioPlatform;
    use CeplokUi\DesignKit\Enums\RadioState;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Radio Button — satu pilihan dari beberapa opsi yang saling meniadakan.

    Memakai `<input type="radio">` bawaan supaya panah keyboard, atribut
    `name`, dan pembaca layar tetap berfungsi; hanya tampilannya yang digambar
    ulang. Pilihan aktif memakai `checked`, `wire:model`, atau `x-model`; di
    dalam Livewire radio yang `value`-nya sama dengan properti `wire:model`
    langsung tercentang di HTML pertama.

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
    $platform = Prop::oneOf(RadioPlatform::class, $platform, RadioPlatform::Default, 'radio', 'platform');
    $state = Prop::oneOf(RadioState::class, $state, RadioState::Default, 'radio', 'state');
    $application = Prop::oneOf(RadioApplication::class, $application, RadioApplication::Default, 'radio', 'application');
    $darkMode = Prop::bool($darkMode, false, 'radio', 'dark-mode');

    $id = FieldId::for($attributes, 'radio');
    $helperId = $id.'-helper';
    $inactive = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true) || $state === 'inactive';
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);
    $hasText = $hasLabel || $hasHelper;

    // Ukuran kontrol, jarak turun agar sejajar tengah baris label, dan ukuran
    // teks label per platform. Caption tetap 12px di kedua platform.
    $platforms = [
        'default' => ['control' => 'size-4', 'offset' => 'mt-0.5', 'label' => 'text-sm'],
        'mobile' => ['control' => 'size-3.5', 'offset' => 'mt-px', 'label' => 'text-xs'],
    ];

    // Cincin 3.5px inilah yang menandai pilihan aktif: karena
    // box-sizing:border-box, garis setebal itu menyisakan lingkaran kecil
    // berwarna latar di tengah — 9px di desktop dan 7px di mobile.
    $accents = [
        'default' => 'checked:border-primary-700 focus-visible:outline-primary-700',
        'purple' => 'checked:border-purple-500 focus-visible:outline-purple-500',
    ];

    $p = $platforms[$platform];
    $tag = $hasText ? 'div' : 'span';
    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'type', 'disabled'])
        ->merge([
            'aria-describedby' => $hasHelper ? $helperId : null,
            'checked' => $attributes->has('checked') ? null : Bound::checked($attributes, 'radio'),
        ])
        ->class([
            'shrink-0 appearance-none rounded-full border transition-colors',
            'focus-visible:outline-2 focus-visible:outline-offset-2',
            'disabled:cursor-not-allowed',
            $p['control'],
            // Kedua cabang tak pernah aktif bersamaan, jadi tak ada kelas yang saling menimpa.
            $inactive
                ? 'checked:border-[3.5px] border-radio-border-inactive bg-field-disabled checked:border-radio-ring-inactive'
                : 'cursor-pointer checked:border-[3.5px] border-radio-border bg-radio-surface '.$accents[$application],
            $p['offset'] => $hasText,
        ]);
@endphp

<{{ $tag }} {{ $root->class([$hasText ? 'flex items-start gap-2' : 'inline-flex']) }}>
    <input type="radio" id="{{ $id }}" @disabled($inactive) {{ $input }} />

    @if ($hasText)
        <div class="min-w-0">
            @if ($hasLabel)
                <label
                    for="{{ $id }}"
                    @class(['block font-bold', $p['label'], $inactive ? 'cursor-not-allowed text-radio-content-inactive' : 'cursor-pointer text-radio-label'])
                >{{ $label }}</label>
            @endif

            @if ($hasHelper)
                <p id="{{ $helperId }}" @class(['mt-0.5 text-xs', $inactive ? 'text-radio-content-inactive' : 'text-content-muted'])>{{ $helperText }}</p>
            @endif
        </div>
    @endif
</{{ $tag }}>
