@php
    use CeplokUi\DesignKit\Enums\FloatingLabelApplication;
    use CeplokUi\DesignKit\Enums\FloatingLabelPlatform;
    use CeplokUi\DesignKit\Enums\FloatingLabelState;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\ClassToggle;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\Support\Js;
@endphp

{{--
    Floating Label — isian teks yang labelnya naik ke garis atas begitu field
    difokus atau berisi.

    Label naik sendiri, jadi `state` hanya perlu diisi untuk mengunci
    tampilannya (`active`) atau menandai kesalahan (`error`, yang sekaligus
    memasang `aria-invalid`). Keterisiannya ikut berubah saat nilainya diganti
    lewat `wire:model` atau `x-model`, dan di dalam Livewire nilai awal
    `wire:model` ikut dirender server, jadi label sudah di posisi yang benar
    sejak HTML pertama. `clearable` menampilkan tombol hapus (×) yang
    mengosongkan isian lalu memicu event `clear`, padanan onClear di React.

    Atribut lain (`name`, `wire:model`, `placeholder`, …) diteruskan ke
    `<input>`-nya; `class` dan `wire:key` ke pembungkus terluar. `label`,
    `helper-text`, dan `icon` bisa prop atau slot. `dark-mode` memasang
    `data-theme="dark"`.
--}}
@props([
    'label' => null,
    'helperText' => null,
    'icon' => null,
    'platform' => 'default',
    'state' => 'default',
    'application' => 'default',
    'clearable' => false,
    'darkMode' => false,
])

@php
    $platform = Prop::oneOf(FloatingLabelPlatform::class, $platform, FloatingLabelPlatform::Default, 'floating-label', 'platform');
    $state = Prop::oneOf(FloatingLabelState::class, $state, FloatingLabelState::Default, 'floating-label', 'state');
    $application = Prop::oneOf(FloatingLabelApplication::class, $application, FloatingLabelApplication::Default, 'floating-label', 'application');
    $clearable = Prop::bool($clearable, false, 'floating-label', 'clearable');
    $darkMode = Prop::bool($darkMode, false, 'floating-label', 'dark-mode');

    $id = FieldId::for($attributes, 'floating-label');
    $helperId = $id.'-helper';
    $disabled = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true);
    $hasIcon = Content::filled($icon);
    $hasHelper = Content::filled($helperText);
    $placeholder = $attributes->get('placeholder');

    // Tinggi kotak dan ukuran teks isian per platform. Label yang sudah naik
    // selalu 12px di kedua platform.
    $platforms = [
        'default' => ['box' => 'h-14.5', 'text' => 'text-sm'],
        'mobile' => ['box' => 'h-12.5', 'text' => 'text-xs'],
    ];

    // Label yang naik satu tingkat lebih terang di tampilan gelap; nilainya
    // diatur token floating-label-accent.
    $accents = [
        'default' => ['border' => 'border-primary-600', 'label' => 'text-floating-label-accent'],
        'purple' => ['border' => 'border-purple-500', 'label' => 'text-floating-label-accent-purple'],
    ];

    $p = $platforms[$platform];
    $accent = $accents[$application];
    $isError = $state === 'error';
    $locked = $state !== 'default';
    $active = $state === 'active';

    // Keadaan awal dari server: nilai atribut `value` atau properti wire:model.
    $value = $attributes->get('value') ?? Bound::text($attributes);
    $filled = $value !== null && $value !== '';

    // Ekspresi Alpine padanan `floated` dan `accented` di kit React.
    $floated = $locked ? 'true' : '(ceplokFocused || ceplokFilled)';
    $accented = $active ? 'true' : 'ceplokFocused';

    // Keadaan yang dikunci `state` dipasang statis; hanya yang bergantung pada
    // fokus dan keterisian yang diikat Alpine.
    $box = (new ClassToggle)->always('flex items-center gap-3 rounded-lg border px-4 transition-colors bg-surface '.$p['box']);
    match (true) {
        $isError => $box->always('border-red-600'),
        $active => $box->always($accent['border']),
        default => $box->when($accented, false, $accent['border'], 'border-border-strong'),
    };

    // Label menumpang di atas kotak: saat turun ia sejajar dengan teks isian
    // (bergeser bila ada ikon), saat naik ia selalu di x=12 menimpa garis
    // atas. Latarnya sama dengan latar kotak, jadi garis di belakangnya
    // tertutup — itulah yang membuat label seolah menempel.
    $labelClasses = (new ClassToggle)->always('pointer-events-none absolute -translate-y-1/2 px-1 transition-all duration-150 ease-out bg-surface');
    $locked
        ? $labelClasses->always('top-0 left-3 text-xs')
        : $labelClasses->when($floated, $filled, 'top-0 left-3 text-xs', 'top-1/2 '.$p['text'].' '.($hasIcon ? 'left-10' : 'left-3'));

    // Satu warna saja yang terpasang pada satu waktu: dua kelas warna
    // sekaligus diputuskan urutan CSS Tailwind, bukan urutan di sini.
    match (true) {
        $isError => $labelClasses->always('text-feedback-error'),
        $active => $labelClasses->always($accent['label']),
        default => $labelClasses->when($accented, false, $accent['label'], $disabled ? 'text-content-disabled' : 'text-content-muted'),
    };

    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'placeholder'])
        ->merge([
            'value' => $attributes->has('value') ? null : Bound::text($attributes),
            // Placeholder baru muncul setelah label naik, supaya keduanya tak bertumpuk.
            'placeholder' => $placeholder !== null && ($locked || $filled) ? $placeholder : null,
            'x-bind:placeholder' => $placeholder !== null && ! $locked ? $floated.' ? '.Js::from($placeholder).' : null' : null,
            'aria-invalid' => $isError ? 'true' : null,
            'aria-describedby' => $hasHelper ? $helperId : null,
        ])
        ->class('min-w-0 flex-1 bg-transparent outline-none disabled:cursor-not-allowed text-floating-label-content placeholder:text-gray-500 disabled:text-content-disabled '.$p['text']);
@endphp

<div {{ $root->class('w-full') }} x-data="ceplokFloatingLabel">
    <div class="relative">
        <div class="{{ $box->classes() }}" @if ($box->binding()) x-bind:class="{{ $box->binding() }}" @endif>
            @if ($hasIcon)
                <span @class(['flex shrink-0 items-center', $isError ? 'text-floating-label-icon-error' : 'text-floating-label-content'])>{{ $icon }}</span>
            @endif

            <input id="{{ $id }}" {{ $input }} />

            @if ($clearable)
                <button
                    type="button"
                    x-on:click="ceplokClear()"
                    @disabled($disabled)
                    aria-label="Kosongkan isian"
                    @class([
                        'shrink-0 rounded-sm transition-opacity hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current disabled:cursor-not-allowed',
                        $isError ? 'text-floating-label-icon-error' : 'text-content-muted',
                    ])
                >
                    <svg class="size-2.5" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                        <path stroke-linecap="round" d="M1 1l8 8M9 1L1 9" />
                    </svg>
                </button>
            @endif
        </div>

        <label for="{{ $id }}" class="{{ $labelClasses->classes() }}" @if ($labelClasses->binding()) x-bind:class="{{ $labelClasses->binding() }}" @endif>{{ $label }}</label>
    </div>

    @if ($hasHelper)
        <p id="{{ $helperId }}" @class(['mt-2 text-sm', $isError ? 'text-feedback-error' : 'text-content-muted'])>{{ $helperText }}</p>
    @endif
</div>
