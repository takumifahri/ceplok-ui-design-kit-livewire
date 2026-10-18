@php
    use CeplokUi\DesignKit\Enums\BadgeSize;
    use CeplokUi\DesignKit\Enums\BadgeVariant;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Badge — label status kecil, opsional dengan ikon (slot `icon`) dan tombol
    tutup (`dismissible`).

    Tombol tutup memicu event `dismiss` (padanan onDismiss di React), mis.
    `wire:dismiss="hapusFilter('aktif')"`. Tampil/sembunyinya bisa dikendalikan
    dari luar lewat `wire:model` (nilai awalnya dibaca dari properti
    Livewire-nya) atau `x-model` (beri juga `:open` sesuai nilai awalnya);
    `:open="false"` menyembunyikannya sejak awal. Perilaku itu butuh plugin
    Alpine kit.

    `dark-mode` membalik pasangan warnanya: latar -900 (gray-700) dan teks -300.
--}}
@props([
    'variant' => 'gray',
    'size' => 'sm',
    'icon' => null,
    'dismissible' => false,
    'open' => null,
    'darkMode' => false,
])

@php
    $variant = Prop::oneOf(BadgeVariant::class, $variant, BadgeVariant::Gray, 'badge', 'variant');
    $size = Prop::oneOf(BadgeSize::class, $size, BadgeSize::Sm, 'badge', 'size');
    $dismissible = Prop::bool($dismissible, false, 'badge', 'dismissible');
    // Tanpa `open`, nilai awal yang diikat wire:model dibaca dari properti
    // komponen Livewire-nya, jadi HTML pertama dari server sudah benar.
    $bound = Bound::has($attributes);
    $open = $open === null
        ? ($bound ? Bound::bool($attributes, true) : true)
        : Prop::bool($open, true, 'badge', 'open');
    $darkMode = Prop::bool($darkMode, false, 'badge', 'dark-mode');

    // Ikon dan teks memakai satu warna (currentColor). Tombol tutup ikut warna
    // itu, kecuali variant gray yang tombolnya lebih pucat, sesuai desain.
    $variants = [
        'gray' => ['light' => 'bg-gray-100 text-gray-900', 'dark' => 'bg-gray-700 text-gray-300', 'close' => 'text-gray-500', 'closeDark' => 'text-gray-400'],
        'brand' => ['light' => 'bg-primary-100 text-primary-800', 'dark' => 'bg-primary-900 text-primary-300'],
        'danger' => ['light' => 'bg-red-100 text-red-800', 'dark' => 'bg-red-900 text-red-300'],
        'warning' => ['light' => 'bg-yellow-100 text-yellow-900', 'dark' => 'bg-yellow-900 text-yellow-300'],
        'success' => ['light' => 'bg-green-100 text-green-900', 'dark' => 'bg-green-900 text-green-300'],
    ];

    // Tinggi dikunci (24px / 28px) karena teksnya dipangkas ke tinggi huruf
    // kapital (lihat $label), jadi tinggi badge tidak datang dari line-height.
    $sizes = [
        'sm' => ['root' => 'h-6 gap-1.5 text-sm', 'icon' => 'size-3.5', 'close' => 'size-3.5'],
        'lg' => ['root' => 'h-7 gap-2 text-base', 'icon' => 'size-5', 'close' => 'size-4'],
    ];

    // `text-box: trim-both cap alphabetic` memangkas ruang ascender/descender,
    // sehingga yang ditengahkan `items-center` adalah huruf kapitalnya. Tanpa
    // ini teks Lato tampak lebih rendah dari ikon dan tombol ×. Peramban yang
    // belum mendukung `text-box` tetap memakai perataan biasa.
    $label = 'leading-none [text-box:trim-both_cap_alphabetic]';

    $v = $variants[$variant];
    $s = $sizes[$size];
    $closeColor = $darkMode ? ($v['closeDark'] ?? null) : ($v['close'] ?? null);
    $interactive = $dismissible || $bound;
@endphp

@if ($interactive || $open)
    <span
        @if ($interactive)
            x-data="ceplokDismissible"
            x-modelable="open"
            x-show="shown"
            @if ($dismissible)
                x-bind:style="leaving ? { opacity: 0 } : {}"
            @endif
        @endif
        {{ $attributes
            ->class([
                'inline-flex items-center rounded-md px-3',
                $s['root'],
                $darkMode ? $v['dark'] : $v['light'],
                'transition-opacity duration-200 opacity-100 animate-ceplok-fade-in' => $dismissible,
            ])
            ->merge($interactive && ! $open ? ['style' => 'display: none;'] : []) }}
    >
        {{--
            Bukan <x-ceplok::icon>: Icon membawa size-5 bawaan yang mengalahkan
            size-3.5, sehingga ikon di badge kecil jadi 20px — lebih besar dari
            teksnya.
        --}}
        @if (Content::filled($icon))
            <span aria-hidden="true" class="inline-flex shrink-0 items-center justify-center [&>svg]:size-full {{ $s['icon'] }}">{{ $icon }}</span>
        @endif
        @if ($slot->isNotEmpty())
            <span class="{{ $label }}">{{ $slot }}</span>
        @endif
        @if ($dismissible)
            <button
                type="button"
                x-on:click="close()"
                @class([
                    '-mr-0.5 inline-flex shrink-0 rounded-sm transition-opacity hover:opacity-70 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current',
                    $closeColor => $closeColor !== null,
                ])
                aria-label="Hapus"
            >
                {{ svg('ceplok-o-close', $s['close']) }}
            </button>
        @endif
    </span>
@endif
