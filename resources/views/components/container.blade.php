@php
    use CeplokUi\DesignKit\Enums\ContainerSize;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Container — pembungkus lebar konten yang konsisten. Desain: desktop maksimal
    1126px dan terpusat; mobile maksimal 380px dengan rounded-xl.

    `size` hanya menggeser batas desktop itu: `prose` 720px untuk teks panjang
    dan formulir, `default` 1126px, `wide` 1440px untuk tabel atau dasbor,
    `full` tanpa batas (hanya padding). Pemusatan, padding (20px → 56px), dan
    ambangnya sama di semua varian; `:padded="false"` mematikan padding bila
    induknya sudah punya padding sendiri. `as` mengganti tag luarnya
    (bawaan `div`).

    Ambang 640/1024/1280px diukur dari lebar ruang yang tersedia (@container),
    bukan lebar viewport, jadi tetap benar di dalam kolom atau sidebar. Elemen
    luar hanya titik ukur dan pemegang `class` milik pemakai; lebar, pemusatan,
    dan padding hidup di elemen dalam karena sebuah elemen tidak bisa mengueri
    container-nya sendiri. Elemen luar tetap diberi `mx-auto` supaya Container
    tetap di tengah bila pemakai memasang `max-w-*` lewat `class`.

    Di tema gelap elemen dalam diberi latar token `container` (gray-700) dan
    teks `content-subtle` (gray-300). `dark-mode` sama dengan memasang
    `data-theme="dark"`; di tema terang Container transparan.
--}}
@props([
    'as' => 'div',
    'size' => 'default',
    'padded' => true,
    'darkMode' => false,
])

@php
    $tag = Prop::tag($as, 'div', 'container', 'as');
    $size = Prop::oneOf(ContainerSize::class, $size, ContainerSize::Default, 'container', 'size');
    $padded = Prop::bool($padded, true, 'container', 'padded');
    $darkMode = Prop::bool($darkMode, false, 'container', 'dark-mode');

    // Di ruang sempit semua varian sama — 380px + rounded-xl mengikuti desain
    // mobile; yang membedakan hanya batas di ≥ 640px. `full` tidak ikut dijepit
    // 380px karena tujuannya justru mengisi penuh.
    $sizes = [
        'prose' => 'max-w-[380px] rounded-xl @[640px]:max-w-[720px] @[640px]:rounded-none',
        'default' => 'max-w-[380px] rounded-xl @[640px]:max-w-[1126px] @[640px]:rounded-none',
        'wide' => 'max-w-[380px] rounded-xl @[640px]:max-w-[1440px] @[640px]:rounded-none',
        'full' => 'max-w-none',
    ];
@endphp

<{{ $tag }} {{ $attributes->class('@container mx-auto')->merge($darkMode ? ['data-theme' => 'dark'] : []) }}>
    <div
        @class([
            'mx-auto w-full',
            $sizes[$size],
            'px-5 @[640px]:px-8 @[1024px]:px-12 @[1280px]:px-14' => $padded,
            'in-data-[theme=dark]:bg-container in-data-[theme=dark]:text-content-subtle',
        ])
    >
        {{ $slot }}
    </div>
</{{ $tag }}>
