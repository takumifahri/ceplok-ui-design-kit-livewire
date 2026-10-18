@php
    use CeplokUi\DesignKit\Enums\InputFieldApplication;
    use CeplokUi\DesignKit\Enums\InputFieldPlatform;
    use CeplokUi\DesignKit\Enums\InputFieldState;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Input Field — satu baris isian teks dengan label dan caption opsional.

    State `inactive` otomatis menonaktifkan input dan `failed` menandainya
    `aria-invalid`, jadi tampilan dan makna aksesibilitasnya selalu sejalan.
    Garis aksen saat difokus mengikuti `application`, juga di tampilan gelap.

    `type="password"` menyamarkan isian, memasang ikon gembok, dan menambahkan
    tombol mata untuk menampilkan kata sandinya. `clearable` menampilkan tombol
    hapus (×) yang mengosongkan isian lalu memicu event `clear`, padanan
    onClear di React (mis. `wire:clear="resetCari"`).

    Atribut lain (`type`, `name`, `wire:model`, `placeholder`, …) diteruskan ke
    `<input>`-nya; `class` dan `wire:key` ke pembungkus terluar. Di dalam
    Livewire nilai awal `wire:model` ikut dirender server. `label`,
    `helper-text`, dan `icon` bisa prop atau slot; `:icon="false"` melepas
    gembok bawaan password. `dark-mode` memasang `data-theme="dark"`.
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
    $platform = Prop::oneOf(InputFieldPlatform::class, $platform, InputFieldPlatform::Default, 'input-field', 'platform');
    $state = Prop::oneOf(InputFieldState::class, $state, InputFieldState::Default, 'input-field', 'state');
    $application = Prop::oneOf(InputFieldApplication::class, $application, InputFieldApplication::Default, 'input-field', 'application');
    $clearable = Prop::bool($clearable, false, 'input-field', 'clearable');
    $darkMode = Prop::bool($darkMode, false, 'input-field', 'dark-mode');

    $id = FieldId::for($attributes, 'input-field');
    $helperId = $id.'-helper';
    $isPassword = $attributes->get('type') === 'password';
    $isDisabled = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true) || $state === 'inactive';
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);

    $platforms = ['default' => 'h-13', 'mobile' => 'h-10'];

    // Garis aksen per aplikasi: dipakai state `typing` dan saat field benar-benar difokus.
    $accents = [
        'default' => ['border' => 'border-primary-500', 'focus' => 'focus-within:border-primary-500'],
        'purple' => ['border' => 'border-purple-500', 'focus' => 'focus-within:border-purple-500'],
    ];

    // Latar dan garis per state, semuanya token, jadi tema gelap cukup
    // mengganti nilai token lewat data-theme.
    $surfaces = [
        'default' => 'border-field-border bg-field',
        'typing' => 'bg-field',
        'inactive' => 'border-input-field-border-inactive bg-field',
        'failed' => 'border-feedback-error bg-input-field-surface-failed',
    ];

    // Warna teks per state: label, isi field, ikon, caption, tombol hapus,
    // dan tombol mata punya tingkat kontras yang berbeda.
    $inks = [
        'default' => [
            'label' => 'text-content',
            'field' => 'text-content placeholder:text-content-muted',
            'helper' => 'text-content-muted',
            'icon' => 'text-content-muted',
            'clear' => 'text-content-muted',
            'reveal' => 'text-content-muted',
        ],
        'typing' => [
            'label' => 'text-content',
            'field' => 'text-content placeholder:text-gray-500',
            'helper' => 'text-content-muted',
            'icon' => 'text-gray-500',
            'clear' => 'text-content-muted',
            'reveal' => 'text-content-muted',
        ],
        'inactive' => [
            'label' => 'text-input-field-label-inactive',
            'field' => 'text-content-disabled placeholder:text-content-disabled',
            'helper' => 'text-content-disabled',
            'icon' => 'text-content-disabled',
            'clear' => 'text-gray-400',
            'reveal' => 'text-input-field-action-inactive',
        ],
        'failed' => [
            'label' => 'text-content',
            'field' => 'text-input-field-content-failed placeholder:text-red-600',
            'helper' => 'text-red-600',
            'icon' => 'text-red-600',
            'clear' => 'text-red-600',
            'reveal' => 'text-content-muted',
        ],
    ];

    // Desain password yang failed memakai red-500 di tampilan gelap untuk
    // ikon, placeholder, dan caption; token feedback-error memberi red-600
    // terang dan red-500 gelap.
    $ink = $isPassword && $state === 'failed'
        ? array_merge($inks['failed'], [
            'field' => 'text-input-field-content-failed placeholder:text-feedback-error',
            'helper' => 'text-feedback-error',
            'icon' => 'text-feedback-error',
        ])
        : $inks[$state];
    $accent = $accents[$application];

    // Desain password mobile memakai teks 12px; type teks tetap 14px di kedua platform.
    $textSize = $isPassword && $platform === 'mobile' ? 'text-xs' : 'text-sm';
    $leadingIcon = $icon === false ? null : (Content::filled($icon) ? $icon : null);
    $lockIcon = $isPassword && $icon === null;
    $interactive = $clearable || $isPassword;

    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'disabled'])
        ->merge([
            'aria-invalid' => $state === 'failed' ? 'true' : null,
            'aria-describedby' => $hasHelper ? $helperId : null,
            // Nilai awal wire:model ikut dirender server, kecuali kata sandi.
            'value' => $attributes->has('value') || $isPassword ? null : Bound::text($attributes),
        ] + ($isPassword ? [
            // Kata sandi yang sedang tampil jangan sampai dikoreksi atau dikirim ke pemeriksa ejaan.
            'spellcheck' => 'false',
            'autocapitalize' => 'off',
            'autocorrect' => 'off',
            'x-bind:type' => "ceplokRevealed ? 'text' : 'password'",
        ] : []))
        ->class(['min-w-0 flex-1 bg-transparent outline-none disabled:cursor-not-allowed', $textSize, $ink['field']]);
@endphp

<div {{ $root->class('w-full') }} @if ($interactive) x-data="ceplokInputField" @endif>
    @if ($hasLabel)
        <label for="{{ $id }}" @class(['mb-2 block font-bold', $textSize, $ink['label']])>{{ $label }}</label>
    @endif

    <div
        @class([
            'flex items-center gap-3 rounded-lg border px-4 transition-colors',
            $platforms[$platform],
            $surfaces[$state],
            // `typing` mengunci garis aksen; state lain tetap berubah saat
            // difokus, kecuali failed yang mempertahankan garis merahnya.
            $accent['border'] => $state === 'typing',
            $accent['focus'] => $state === 'default',
        ])
    >
        @if ($leadingIcon !== null || $lockIcon)
            <span @class(['flex shrink-0 items-center', $ink['icon']])>
                @if ($lockIcon)
                    {{-- Gembok bawaan type="password", disalin dari desain: 16px dengan garis 2px. --}}
                    <svg class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4.6667 7.3332V4.6664C4.6667 3.7822 5.0179 2.9343 5.643 2.3092C6.2681 1.684 7.1159 1.3328 8 1.3328C8.8841 1.3328 9.7319 1.684 10.357 2.3092C10.9821 2.9343 11.3333 3.7822 11.3333 4.6664V7.3332M3.3333 7.3332H12.6667C13.403 7.3332 14 7.9302 14 8.6667V13.3337C14 14.0702 13.403 14.6672 12.6667 14.6672H3.3333C2.597 14.6672 2 14.0702 2 13.3337V8.6667C2 7.9302 2.597 7.3332 3.3333 7.3332Z" />
                    </svg>
                @else
                    {{ $leadingIcon }}
                @endif
            </span>
        @endif

        <input id="{{ $id }}" @disabled($isDisabled) {{ $input }} />

        @if ($clearable)
            <button
                type="button"
                x-on:click="ceplokClear()"
                @disabled($isDisabled)
                aria-label="Kosongkan isian"
                @class([
                    'shrink-0 rounded-sm transition-opacity hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current disabled:cursor-not-allowed',
                    $ink['clear'],
                ])
            >
                <svg class="size-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" d="M1 1l10 10M11 1L1 11" />
                </svg>
            </button>
        @endif

        @if ($isPassword)
            {{--
                Ikon mata Flowbite 16px, sebesar gembok di kiri; `after`
                memperluas area tekannya jadi 24px tanpa menggeser ikonnya.
            --}}
            <button
                type="button"
                x-on:click="ceplokRevealed = ! ceplokRevealed"
                @disabled($isDisabled)
                aria-label="Tampilkan kata sandi"
                aria-pressed="false"
                x-bind:aria-pressed="ceplokRevealed"
                aria-controls="{{ $id }}"
                @class([
                    'relative shrink-0 rounded-sm transition-opacity after:absolute after:-inset-1 hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current disabled:cursor-not-allowed',
                    $ink['reveal'],
                ])
            >
                {{ svg('ceplok-o-eye', 'size-4', ['aria-hidden' => 'true', 'x-show' => '! ceplokRevealed']) }}
                {{ svg('ceplok-o-eye-slash', 'size-4', ['aria-hidden' => 'true', 'x-show' => 'ceplokRevealed', 'x-cloak']) }}
            </button>
        @endif
    </div>

    @if ($hasHelper)
        <p id="{{ $helperId }}" @class(['mt-2', $textSize, $ink['helper']])>{{ $helperText }}</p>
    @endif
</div>
