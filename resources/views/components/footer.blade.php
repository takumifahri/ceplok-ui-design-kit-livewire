@php
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Footer — @container: tata letak mengikuti lebar footer, bukan viewport.

    `logo` adalah URL gambar; tanpa itu isi slot `logo-content` yang dipakai.
    `menus` berisi array `label` dan `url`; satu baris memuat paling banyak
    5 menu, sisanya turun ke baris berikutnya, dan tiap baris dipusatkan.
    `copyright` bisa prop atau slot. `socials` berisi array `label`, `url`, dan
    `icon` — markup (mis. hasil svg()) atau nama ikon, mis. `brand-instagram`.
    `fluid` melebarkan isi mengikuti lebar footer (tanpa batas 1280px).
--}}
@props([
    'logo' => null,
    'logoAlt' => 'Logo',
    'logoContent' => null,
    'menus' => [],
    'copyright' => null,
    'socials' => [],
    'fluid' => false,
])

@php
    $fluid = Prop::bool($fluid, false, 'footer', 'fluid');
    $menus = collect($menus)->values();
    $socials = collect($socials)->values();
    $menuRows = $menus->chunk(5);
@endphp

<footer {{ $attributes->class('@container bg-gray-800 text-gray-300') }}>
    <div @class(['flex flex-col gap-8 px-6 py-16 @3xl:px-12', $fluid ? 'w-full' : 'mx-auto max-w-7xl'])>
        <div class="flex flex-col gap-7 @3xl:flex-row @3xl:items-center @3xl:gap-12">
            <div class="shrink-0">
                @if (filled($logo))
                    {{--
                        48px, naik jadi 60px saat footer melebar. Tingginya dipatok
                        dari logo lockup; baris nama instansi di bawahnya berhenti
                        terbaca bila logo lebih kecil dari ini.
                    --}}
                    <img src="{{ $logo }}" alt="{{ $logoAlt }}" class="h-12 w-auto @3xl:h-15" />
                @else
                    {{ $logoContent }}
                @endif
            </div>

            @if ($menus->isNotEmpty())
                <nav class="@3xl:flex-1" aria-label="Navigasi footer">
                    <div class="flex flex-col gap-y-3">
                        @foreach ($menuRows as $row)
                            <ul class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-base">
                                @foreach ($row as $menu)
                                    <li>
                                        <a
                                            href="{{ $menu['url'] ?? '#' }}"
                                            class="rounded text-center transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                                        >{{ $menu['label'] ?? '' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </nav>
            @endif

            <div></div>
        </div>

        <hr class="border-white/15" />

        <div class="flex flex-col gap-6 @xl:flex-row @xl:items-center @xl:justify-between">
            @if (Content::filled($copyright))
                <p class="text-base">{{ $copyright }}</p>
            @endif

            @if ($socials->isNotEmpty())
                <ul class="flex items-center gap-5">
                    @foreach ($socials as $social)
                        @php
                            $url = $social['url'] ?? '#';
                            $external = $url !== '#';
                        @endphp
                        <li>
                            <a
                                href="{{ $url }}"
                                class="block rounded text-gray-400 transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                                aria-label="{{ $social['label'] ?? 'Media sosial' }}"
                                @if ($external) target="_blank" rel="noopener noreferrer" @endif
                            >
                                <x-ceplok::icon class="size-6.5">{!! Content::icon($social['icon']) !!}</x-ceplok::icon>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</footer>
