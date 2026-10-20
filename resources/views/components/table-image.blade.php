@php
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    TableImage — gambar kecil di sel beserta pratinjaunya. Dipakai otomatis
    oleh kolom `image` di Table, dan bisa dipakai langsung di `cell` untuk
    susunan sendiri.

    `caption` (prop atau slot) tampil di bawah gambar kecil bila `show-caption`,
    dan selalu di pratinjau. `preview` (bawaan true) membuat gambar bisa
    diklik untuk dilihat besar. `thumb-class` mengganti ukuran dan sudut
    gambar kecil bawaan `h-6 w-9 rounded` — padanan `className` di React,
    yang di sini dipasang pada `<img>`; `class` tetap ke pembungkus terluar
    seperti komponen kit lain.

    Pratinjaunya `<dialog>` yang baru dibuat saat dibuka lalu dipindah ke
    `body` (lihat resources/js/components/table-preview.js).
--}}
@props([
    'src',
    'alt' => '',
    'caption' => null,
    'showCaption' => true,
    'preview' => true,
    'thumbClass' => null,
])

@php
    $showCaption = Prop::bool($showCaption, true, 'table-image', 'show-caption');
    $preview = Prop::bool($preview, true, 'table-image', 'preview');
    $hasCaption = Content::filled($caption);
    $alt = (string) $alt;
    // `thumb-class` mengganti ukuran bawaan, bukan menambahinya: `size-8` kalah
    // dari `h-6 w-9` di urutan CSS bila keduanya terpasang.
    $thumb = 'object-cover '.(filled($thumbClass) ? $thumbClass : 'h-6 w-9 rounded');
@endphp

<span @if ($preview) x-data="ceplokTablePreview" @endif {{ $attributes->class('inline-flex flex-col items-center gap-1') }}>
    @if ($preview)
        <button
            type="button"
            x-on:click="show()"
            aria-label="{{ $alt !== '' ? 'Lihat gambar '.$alt : 'Lihat gambar' }}"
            class="cursor-zoom-in rounded transition-opacity hover:opacity-80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
        >
            <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $thumb }}">
        </button>
    @else
        <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $thumb }}">
    @endif

    @if ($showCaption && $hasCaption)
        {{ $caption }}
    @endif

    @if ($preview)
        {{-- Klik di latar mengenai <dialog> itu sendiri, bukan isinya, jadi cukup x-on:click.self. --}}
        <template x-if="open">
            <template x-teleport="body">
                <dialog
                    x-init="$el.showModal()"
                    x-on:cancel.prevent="close()"
                    x-on:click.self="close()"
                    aria-label="{{ $alt !== '' ? $alt : 'Pratinjau gambar' }}"
                    class="m-auto max-h-none max-w-none overflow-visible bg-transparent p-4 backdrop:bg-gray-900/80 backdrop:backdrop-blur-sm"
                >
                    <button
                        type="button"
                        x-on:click="close()"
                        aria-label="Tutup pratinjau"
                        class="fixed top-4 right-4 inline-flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >
                        {{ svg('ceplok-o-close', 'size-5', ['aria-hidden' => 'true']) }}
                    </button>
                    <figure class="flex flex-col items-center gap-3">
                        <img src="{{ $src }}" alt="{{ $alt }}" class="max-h-[80vh] max-w-[90vw] rounded-lg bg-white object-contain shadow-xl">
                        @if ($hasCaption)
                            <figcaption class="text-center text-sm text-white">{{ $caption }}</figcaption>
                        @endif
                    </figure>
                </dialog>
            </template>
        </template>
    @endif
</span>
