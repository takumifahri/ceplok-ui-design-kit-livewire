@php
    use CeplokUi\DesignKit\Enums\HeroCenteredContent;
    use CeplokUi\DesignKit\Enums\HeroImageOrientation;
    use CeplokUi\DesignKit\Enums\HeroPlatform;
    use CeplokUi\DesignKit\Enums\HeroType;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Hero — blok pembuka halaman: judul, sub heading, deskripsi, satu tombol,
    dan (untuk dua tipe horizontal) satu gambar pendamping.

    `type`: `horizontal-image-left` (bawaan), `horizontal-image-right`, atau
    `centered`. `platform="mobile"` memaksa susunan bertumpuk berapa pun
    lebarnya. `show-heading` mengatur baris sub heading — judul utama selalu
    ada sebagai <h1>. Tombol menjadi <a> bila `button-href` diisi;
    `button-attributes` menambah atribut ke tombolnya, padanan onButtonClick
    di React, mis. `['wire:click' => 'mulai']`.

    Gambar lewat `image` (URL) atau slot `image-content` (mis. <svg> inline).
    `image-orientation` hanya mengatur lebar kolom gambarnya; tinggi gambar
    tetap mengikuti rasio asli berkasnya. Pada `centered`, gambar hanya dipakai
    bila `centered-content="image"`: gambarnya mengisi penuh hero tanpa padding,
    dan judulnya tetap ada sebagai <h1> khusus pembaca layar.

    Desain: bingkai 1440px, padding 24px atas-bawah; tipe horizontal setinggi
    762px, tipe centered ±474px. Tinggi 762px dipasang sebagai min-height
    supaya teks panjang menambah tinggi hero alih-alih meluber. Ambang
    susunannya diukur dari lebar hero itu sendiri (@container).

    Latar dan teks dari token, jadi `dark-mode` cukup memasang
    `data-theme="dark"`: latar gray-800, judul putih, sub heading primary-400,
    deskripsi gray-300. Tombolnya tetap primary-700.
--}}
@props([
    'type' => 'horizontal-image-left',
    'centeredContent' => 'text',
    'platform' => 'desktop',
    'heading' => null,
    'subHeading' => null,
    'description' => null,
    'showHeading' => true,
    'showButton' => true,
    'buttonLabel' => 'Lihat Lebih Lanjut',
    'buttonHref' => null,
    'buttonAttributes' => [],
    'image' => null,
    'imageAlt' => '',
    'imageOrientation' => 'portrait',
    'imageContent' => null,
    'darkMode' => false,
])

@php
    $type = Prop::oneOf(HeroType::class, $type, HeroType::HorizontalImageLeft, 'hero', 'type');
    $centeredContent = Prop::oneOf(HeroCenteredContent::class, $centeredContent, HeroCenteredContent::Text, 'hero', 'centered-content');
    $platform = Prop::oneOf(HeroPlatform::class, $platform, HeroPlatform::Desktop, 'hero', 'platform');
    $imageOrientation = Prop::oneOf(HeroImageOrientation::class, $imageOrientation, HeroImageOrientation::Portrait, 'hero', 'image-orientation');
    $showHeading = Prop::bool($showHeading, true, 'hero', 'show-heading');
    $showButton = Prop::bool($showButton, true, 'hero', 'show-button');
    $darkMode = Prop::bool($darkMode, false, 'hero', 'dark-mode');

    $centered = $type === 'centered';
    $stacked = $platform === 'mobile';
    $hasImage = filled($image) || Content::filled($imageContent);
    $hasMedia = ! $centered && $hasImage;
    $centeredImage = $centered && $centeredContent === 'image' && $hasImage;

    // Latar: surface (putih), atau primary-50 untuk centered karena tanpa
    // gambar latar itulah yang memberinya bentuk. Tema gelap: surface-subtle
    // (gray-800) — gelapnya bisa dari hero itu sendiri (data-*) maupun dari
    // pembungkus (in-data-*).
    $sectionBg = ($centered ? 'bg-primary-50' : 'bg-surface')
        .' data-[theme=dark]:bg-surface-subtle in-data-[theme=dark]:bg-surface-subtle';
    $root = $attributes->merge($darkMode ? ['data-theme' => 'dark'] : []);

    // Saat bertumpuk, tombol tipe horizontal melebar penuh, seperti di frame
    // mobile desain. Tipe centered tidak: tombolnya seukuran teks dan terpusat.
    // Class dari `button-attributes` disambung setelahnya, tidak menimpanya.
    $buttonAttributes = collect($buttonAttributes)->all();
    $buttonWidth = $centered ? null : ($stacked ? 'w-full' : 'w-full @[768px]:w-auto');
    $buttonClass = trim(($buttonWidth ?? '').' '.($buttonAttributes['class'] ?? '')) ?: null;
    $button = new ComponentAttributeBag(array_merge(
        array_diff_key($buttonAttributes, ['class' => true]),
        filled($buttonHref) ? ['as' => 'anchor', 'href' => $buttonHref] : [],
    ));

    // Bertumpuk = rata tengah, dua kolom = rata kiri — ditentukan lebar yang
    // tersedia, bukan jenis perangkatnya.
    $alignLeftWhenWide = ! $centered && ! $stacked;

    // Lebar kolom gambar per orientasi, mengikuti kotak gambar di desain:
    // potret 386px, lanskap 435px. Saat bertumpuk, gambar potret dijepit 270px
    // dan dipusatkan; gambar lanskap mengisi lebar yang tersedia.
    $mediaWidth = $imageOrientation === 'landscape'
        ? ['stacked' => 'w-full', 'wide' => '@[768px]:w-[380px] @[1024px]:w-[435px]']
        : ['stacked' => 'mx-auto w-full max-w-[270px]', 'wide' => '@[768px]:mx-0 @[768px]:w-[320px] @[768px]:max-w-none @[1024px]:w-[386px]'];
@endphp

@if ($centeredImage)
    <section {{ $root->class(['w-full', $sectionBg]) }}>
        @if (Content::filled($heading))
            <h1 class="sr-only">{{ $heading }}</h1>
        @endif
        @if (filled($image))
            <img src="{{ $image }}" alt="{{ $imageAlt }}" class="block h-auto w-full" />
        @else
            {{ $imageContent }}
        @endif
    </section>
@else
    <section {{ $root->class(['@container w-full', $sectionBg]) }}>
        {{--
            py-6 = padding 24px atas-bawah dari desain. Kiri-kanan dicapai lewat
            padding bertingkat sampai 76px (px-19) — jarak tepi yang sama pada
            bingkai 1440px — supaya di layar sempit isinya tidak menempel ke tepi.
        --}}
        <div
            @class([
                'mx-auto flex w-full max-w-[1440px] flex-col justify-center px-5 py-6',
                '@[640px]:px-8 @[1024px]:px-12 @[1280px]:px-19',
                '@[1024px]:min-h-[762px]' => $hasMedia && ! $stacked,
            ])
        >
            @if ($hasMedia)
                {{-- Teks selalu lebih dulu di DOM supaya <h1> dibacakan pertama; letak gambar diatur arah barisnya. --}}
                <div
                    @class([
                        'flex flex-col gap-18',
                        '@[768px]:flex-row @[768px]:items-center @[768px]:gap-12 @[1280px]:gap-24' => ! $stacked,
                        '@[768px]:flex-row-reverse' => ! $stacked && $type === 'horizontal-image-left',
                    ])
                >
            @endif

            <div
                @class([
                    'min-w-0 flex-1 text-center',
                    '@[768px]:text-left' => $alignLeftWhenWide,
                    'mx-auto max-w-3xl py-16 @[768px]:py-24' => $centered,
                ])
            >
                @if (Content::filled($heading))
                    {{-- Judul 48px baru dipakai saat kolomnya cukup lega; di kolom sempit ukuran itu memecah judul jadi tiga baris. --}}
                    <h1 @class(['text-heading-1 font-black tracking-tight text-content', '@[1024px]:text-display' => ! $stacked])>{{ $heading }}</h1>
                @endif

                @if ($showHeading && Content::filled($subHeading))
                    <p class="mt-2 text-body-lg font-bold text-brand">{{ $subHeading }}</p>
                @endif

                @if (Content::filled($description))
                    {{-- 672px — selebar paragraf pengantar dokumentasi, ±74 karakter per baris pada 18px. --}}
                    <p
                        @class([
                            'mx-auto mt-4 max-w-2xl text-body-lg leading-relaxed text-content-subtle',
                            '@[768px]:mx-0' => $alignLeftWhenWide,
                        ])
                    >{{ $description }}</p>
                @endif

                @if ($showButton)
                    <div @class(['mt-8 flex justify-center', '@[768px]:justify-start' => $alignLeftWhenWide])>
                        <x-ceplok::button :attributes="$button" :class="$buttonClass">
                            <x-slot:right-icon>
                                <svg class="size-full" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                                </svg>
                            </x-slot:right-icon>
                            {{ $buttonLabel }}
                        </x-ceplok::button>
                    </div>
                @endif
            </div>

            @if ($hasMedia)
                    <div @class(['shrink-0', $mediaWidth['stacked'], $mediaWidth['wide'] => ! $stacked])>
                        @if (filled($image))
                            <img src="{{ $image }}" alt="{{ $imageAlt }}" class="h-auto w-full rounded-xl shadow-soft" />
                        @else
                            {{ $imageContent }}
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
