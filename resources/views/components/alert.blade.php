@php
    use CeplokUi\DesignKit\Enums\AlertVariant;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Alert — pesan status di dalam alur halaman. Berbeda dari Toast yang
    melayang, Alert menempati ruangnya sendiri. Heading, ikon, tombol aksi
    (slot `actions`), dan tombol tutup semuanya opsional.

    Ikon kiri bawaan mengikuti variant; ganti lewat slot `icon`, atau
    sembunyikan dengan `:icon="false"`. Tombol tutup (`dismissible`, bawaan
    aktif) memicu event `dismiss`, padanan onDismiss di React, mis.
    `wire:dismiss="tandaiDibaca"`. Tampil/sembunyinya bisa dikendalikan lewat
    `wire:model` (nilai awalnya dibaca dari properti Livewire-nya) atau
    `x-model` (beri juga `:open` sesuai nilai awalnya); `:open="false"`
    menyembunyikannya sejak awal. Perilaku itu butuh plugin Alpine kit.

    Warnanya dari token `alert-*`, jadi `dark-mode` cukup memasang
    `data-theme="dark"`: latar -900 variant, ikon -500, heading dan isi -50.
    Isi `actions` tetap milik pemakai — pilih warna tombol yang kontras di
    atas latar -900 variant-nya.
--}}
@props([
    'variant' => 'info',
    'heading' => null,
    'icon' => null,
    'dismissible' => true,
    'open' => null,
    'actions' => null,
    'darkMode' => false,
])

@php
    $variant = Prop::oneOf(AlertVariant::class, $variant, AlertVariant::Info, 'alert', 'variant');
    $dismissible = Prop::bool($dismissible, true, 'alert', 'dismissible');
    // Tanpa `open`, nilai awal yang diikat wire:model dibaca dari properti
    // komponen Livewire-nya, jadi HTML pertama dari server sudah benar.
    $bound = Bound::has($attributes);
    $open = $open === null
        ? ($bound ? Bound::bool($attributes, true) : true)
        : Prop::bool($open, true, 'alert', 'open');
    $darkMode = Prop::bool($darkMode, false, 'alert', 'dark-mode');

    // Nama class ditulis utuh, bukan dirakit dari nama variant: Tailwind hanya
    // membuat class yang namanya muncul utuh di berkas sumber.
    $variants = [
        'success' => [
            'bg' => 'bg-alert-success-surface',
            'iconColor' => 'text-alert-success-icon',
            'heading' => 'text-alert-success-heading',
            'body' => 'text-alert-success-body',
            'close' => 'text-alert-success-close',
            'icon' => 'ceplok-o-check-circle',
        ],
        'danger' => [
            'bg' => 'bg-alert-danger-surface',
            'iconColor' => 'text-alert-danger-icon',
            'heading' => 'text-alert-danger-heading',
            'body' => 'text-alert-danger-body',
            'close' => 'text-alert-danger-close',
            'icon' => 'ceplok-o-close-circle',
        ],
        'warning' => [
            'bg' => 'bg-alert-warning-surface',
            'iconColor' => 'text-alert-warning-icon',
            'heading' => 'text-alert-warning-heading',
            'body' => 'text-alert-warning-body',
            'close' => 'text-alert-warning-close',
            'icon' => 'ceplok-o-exclamation-circle',
        ],
        'info' => [
            'bg' => 'bg-alert-info-surface',
            'iconColor' => 'text-alert-info-icon',
            'heading' => 'text-alert-info-heading',
            'body' => 'text-alert-info-body',
            'close' => 'text-alert-info-close',
            'icon' => 'ceplok-o-info-circle',
        ],
        'purple' => [
            'bg' => 'bg-alert-purple-surface',
            'iconColor' => 'text-alert-purple-icon',
            'heading' => 'text-alert-purple-heading',
            'body' => 'text-alert-purple-body',
            'close' => 'text-alert-purple-close',
            'icon' => 'ceplok-o-info-circle',
        ],
    ];

    $v = $variants[$variant];
    $showIcon = $icon !== false;
    $interactive = $dismissible || $bound;
    $hasHeading = Content::filled($heading);
@endphp

@if ($interactive || $open)
    <div
        @if ($interactive)
            x-data="ceplokDismissible"
            x-modelable="open"
            x-show="shown"
            x-bind:style="leaving ? { opacity: 0 } : {}"
        @endif
        {{ $attributes
            ->class(['rounded-lg p-4 transition-opacity duration-200 sm:p-5 opacity-100', $v['bg'], 'animate-ceplok-fade-in'])
            ->merge(['role' => 'alert'] + ($darkMode ? ['data-theme' => 'dark'] : []) + ($interactive && ! $open ? ['style' => 'display: none;'] : [])) }}
    >
        <div class="flex items-start gap-3">
            @if ($showIcon)
                <x-ceplok::icon :class="'mt-0.5 shrink-0 '.$v['iconColor']">
                    @if (Content::filled($icon))
                        {{ $icon }}
                    @else
                        {{ svg($v['icon']) }}
                    @endif
                </x-ceplok::icon>
            @endif

            <div class="min-w-0 flex-1">
                @if ($hasHeading)
                    <p class="text-sm font-bold {{ $v['heading'] }}">{{ $heading }}</p>
                @endif

                @if ($slot->isNotEmpty())
                    <div @class(['text-sm leading-6', 'mt-1' => $hasHeading, $v['body']])>{{ $slot }}</div>
                @endif

                @if (Content::filled($actions))
                    <div class="mt-3 flex flex-wrap items-center gap-3">{{ $actions }}</div>
                @endif
            </div>

            {{-- Cincin fokus ikut warna heading, bukan warna ×: di tampilan gelap × success, danger, dan warning nyaris sewarna latarnya. --}}
            @if ($dismissible)
                <button
                    type="button"
                    x-on:click="close()"
                    class="shrink-0 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $v['heading'] }}"
                    aria-label="Tutup peringatan"
                >
                    {{ svg('ceplok-o-close', 'size-3.5 '.$v['close']) }}
                </button>
            @endif
        </div>
    </div>
@endif
