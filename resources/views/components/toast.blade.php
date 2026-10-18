@php
    use CeplokUi\DesignKit\Enums\ToastVariant;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Toast — notifikasi sekilas yang melayang di atas konten halaman.

    Desain: kartu 320px beradius 8px, padding 16px, badge ikon 32px berisi
    ikon 20px, dan tombol tutup 12px yang tepat 16px dari tepi kanan kartu.
    Kartunya selalu netral (putih + bayangan); hanya badge ikon yang membawa
    warna variant, supaya notifikasi tetap ringan saat beberapa toast menumpuk.

    Komponennya sendiri tidak memposisikan apa pun: bungkus dengan wadah
    `fixed` (mis. `fixed bottom-4 left-4 z-50 space-y-3`).

    Ikon bawaan mengikuti variant; ganti lewat slot `icon`, atau sembunyikan
    badge-nya dengan `:icon="false"`. Bila `heading` diisi, isi slot tampil
    sebagai teks sekunder di bawahnya. Tombol aksi masuk ke slot `actions`
    (umumnya `w-full`). Tombol tutup (`dismissible`, bawaan aktif) memicu event
    `dismiss`, padanan onDismiss di React. Tampil/sembunyinya bisa dikendalikan
    lewat `wire:model` (nilai awalnya dibaca dari properti Livewire-nya) atau
    `x-model` (beri juga `:open` sesuai nilai awalnya); `:open="false"`
    menyembunyikannya sejak awal. Perilaku itu butuh plugin Alpine kit.

    Warnanya dari token, jadi `dark-mode` cukup memasang `data-theme="dark"`:
    kartu gray-700, teks lebih terang, badge ikon versi gelap. Tombol di
    `actions` tidak ikut diubah — desainnya memakai primary-700 di kedua tampilan.
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
    $variant = Prop::oneOf(ToastVariant::class, $variant, ToastVariant::Info, 'toast', 'variant');
    $dismissible = Prop::bool($dismissible, true, 'toast', 'dismissible');
    // Tanpa `open`, nilai awal yang diikat wire:model dibaca dari properti
    // komponen Livewire-nya, jadi HTML pertama dari server sudah benar.
    $bound = Bound::has($attributes);
    $open = $open === null
        ? ($bound ? Bound::bool($attributes, true) : true)
        : Prop::bool($open, true, 'toast', 'open');
    $darkMode = Prop::bool($darkMode, false, 'toast', 'dark-mode');

    // Warna badge per variant dari token `toast-*`, diambil persis dari desain
    // — termasuk ketidakseragamannya (lihat tokens.css). Nama class ditulis
    // utuh supaya Tailwind membuatnya.
    $variants = [
        'success' => ['badge' => 'bg-toast-success-surface text-toast-success-icon', 'icon' => 'ceplok-o-check'],
        'danger' => ['badge' => 'bg-toast-danger-surface text-toast-danger-icon', 'icon' => 'ceplok-o-close'],
        'warning' => ['badge' => 'bg-toast-warning-surface text-toast-warning-icon', 'icon' => 'ceplok-o-exclamation-circle'],
        'info' => ['badge' => 'bg-toast-info-surface text-toast-info-icon', 'icon' => 'ceplok-o-info-circle'],
        'purple' => ['badge' => 'bg-toast-purple-surface text-toast-purple-icon', 'icon' => 'ceplok-o-info-circle'],
    ];

    $v = $variants[$variant];
    $showIcon = $icon !== false;
    $hasHeading = Content::filled($heading);
    $hasActions = Content::filled($actions);
    $interactive = $dismissible || $bound;

    // Baris rata tengah untuk pesan satu baris; rata atas begitu ada heading
    // atau tombol aksi supaya badge ikon tetap sejajar dengan baris pertama.
    $multiline = $hasHeading || $hasActions;
@endphp

@if ($interactive || $open)
    <div
        @if ($interactive)
            x-data="ceplokDismissible"
            x-modelable="open"
            x-show="shown"
            x-bind:style="leaving ? { opacity: 0, translate: '0 0.5rem', 'transition-duration': '200ms' } : {}"
        @endif
        {{ $attributes
            ->class([
                // `shadow` (bukan shadow-sm) — nilainya persis dua lapis dari desain;
                // --shadow-sm di tokens.css sudah dipakai untuk nilai lain.
                'relative w-full max-w-80 rounded-lg p-4 shadow',
                'bg-surface',
                'transition ease-out',
                'translate-y-0 opacity-100 duration-300',
                'animate-ceplok-toast-in',
            ])
            ->merge(['role' => 'status', 'aria-live' => 'polite'] + ($darkMode ? ['data-theme' => 'dark'] : []) + ($interactive && ! $open ? ['style' => 'display: none;'] : [])) }}
    >
        <div @class(['flex gap-3', $multiline ? 'items-start' : 'items-center'])>
            @if ($showIcon)
                <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $v['badge'] }}">
                    <x-ceplok::icon class="size-5">
                        @if (Content::filled($icon))
                            {{ $icon }}
                        @else
                            {{ svg($v['icon']) }}
                        @endif
                    </x-ceplok::icon>
                </span>
            @endif

            {{--
                Kolom isi memenuhi sisa lebar kartu — tombol aksi ikut selebar itu,
                sesuai desain. Tombol tutup melayang di atasnya, jadi teksnya yang
                diberi ruang kanan supaya tidak tertimpa.
            --}}
            <div class="min-w-0 flex-1">
                @if ($hasHeading)
                    <p @class(['text-sm leading-normal font-bold', 'text-toast-heading', 'pr-6' => $dismissible])>{{ $heading }}</p>
                @endif

                @if ($slot->isNotEmpty())
                    <div @class(['text-sm leading-normal', $hasHeading ? 'text-toast-body' : 'text-content-subtle', 'pr-6' => $dismissible])>{{ $slot }}</div>
                @endif

                @if ($hasActions)
                    <div class="mt-3">{{ $actions }}</div>
                @endif
            </div>
        </div>

        @if ($dismissible)
            {{--
                right-3 + p-1 menaruh ikon 12px tepat 16px dari tepi kartu, sambil
                menyisakan area klik 20px. Ikonnya gray-400 di kedua tampilan.
                Cincin fokus memakai `brand`: primary-600 hanya 2:1 di atas kartu
                gelap, sedangkan nilai gelapnya, primary-400, lebih dari 4:1.
            --}}
            <button
                type="button"
                x-on:click="close()"
                @class([
                    'absolute right-3 rounded-md p-1 text-gray-400 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
                    'hover:text-toast-close-hover focus-visible:text-toast-close-hover focus-visible:outline-brand',
                    $multiline ? 'top-3' : 'top-1/2 -translate-y-1/2',
                ])
                aria-label="Tutup notifikasi"
            >
                {{ svg('ceplok-o-close', 'size-3') }}
            </button>
        @endif
    </div>
@endif
