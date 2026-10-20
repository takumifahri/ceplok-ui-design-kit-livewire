@php
    use CeplokUi\DesignKit\Enums\TableFileType;
    use CeplokUi\DesignKit\Support\Prop;
    use CeplokUi\DesignKit\Support\Table;
@endphp

{{--
    TableFile — ikon jenis berkas + nama berkas di sel. Dipakai otomatis oleh
    kolom `file` di Table, dan bisa dipakai langsung di `cell`.

    `type` (`image` | `pdf` | `file`) bawaannya ditebak dari ekstensi `name`.
    Gambar dan PDF dipratinjau di `<dialog>` yang dipindah ke `body`, sama
    seperti TableImage; PDF memakai penampil bawaan peramban di `<iframe>`.
    `preview="false"` membuka berkasnya di tab baru. Berkas lain tidak bisa
    dipratinjau peramban, jadi tautannya mengunduh. Tanpa `href` nama tampil
    sebagai teks biasa.

    Beda dari React: tombol pratinjau dibungkus `<span class="contents">`
    pembawa x-data, karena keadaan buka-tutupnya butuh elemen sendiri.
    `contents` membuat pembungkus itu tidak ikut tata letak.
--}}
@props([
    'name',
    'href' => null,
    'type' => null,
    'preview' => true,
])

@php
    $name = (string) $name;
    $kind = $type === null ? Table::fileType($name) : Prop::oneOf(TableFileType::class, $type, TableFileType::File, 'table-file', 'type');
    $preview = Prop::bool($preview, true, 'table-file', 'preview');
    $href = filled($href) ? (string) $href : null;

    // Ikon dan warnanya per jenis berkas, mengikuti desain tabel.
    $icons = [
        'image' => ['icon' => 'ceplok-s-image', 'color' => 'text-primary-700'],
        'pdf' => ['icon' => 'ceplok-s-file-pdf', 'color' => 'text-red-500'],
        'file' => ['icon' => 'ceplok-s-file', 'color' => 'text-gray-400'],
    ];
    $link = 'inline-flex max-w-full items-center gap-3 rounded text-left transition-colors hover:text-gray-900 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600';
    $mode = match (true) {
        $href === null => 'text',
        $kind === 'file' || ! $preview => 'link',
        default => 'preview',
    };
@endphp

@php ob_start(); @endphp
<span class="size-6 shrink-0 {{ $icons[$kind]['color'] }}">{{ svg($icons[$kind]['icon'], 'size-full', ['aria-hidden' => 'true']) }}</span>
<span class="truncate">{{ $name }}</span>
@php $label = (string) ob_get_clean(); @endphp

@if ($mode === 'text')
    <span {{ $attributes->class('inline-flex max-w-full items-center gap-3') }}>{!! $label !!}</span>
@elseif ($mode === 'link')
    <a
        href="{{ $href }}"
        target="_blank"
        rel="noreferrer"
        @if ($kind === 'file') download="{{ $name }}" @endif
        {{ $attributes->class($link) }}
    >{!! $label !!}</a>
@else
    <span x-data="ceplokTablePreview" {{ $attributes->class('contents') }}>
        <button type="button" x-on:click="show()" aria-label="Lihat {{ $name }}" class="{{ $link }} cursor-pointer">{!! $label !!}</button>

        <template x-if="open">
            <template x-teleport="body">
                <dialog
                    x-init="$el.showModal()"
                    x-on:cancel.prevent="close()"
                    x-on:click.self="close()"
                    aria-label="Pratinjau {{ $name }}"
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
                        @if ($kind === 'pdf')
                            <iframe src="{{ $href }}" title="{{ $name }}" class="h-[80vh] w-[90vw] max-w-4xl rounded-lg bg-white shadow-xl"></iframe>
                        @else
                            <img src="{{ $href }}" alt="{{ $name }}" class="max-h-[80vh] max-w-[90vw] rounded-lg bg-white object-contain shadow-xl">
                        @endif
                        <figcaption class="flex items-center gap-3 text-sm text-white">
                            {{ $name }}
                            <a href="{{ $href }}" target="_blank" rel="noreferrer" class="font-bold underline underline-offset-2 hover:text-primary-200">Buka di tab baru</a>
                        </figcaption>
                    </figure>
                </dialog>
            </template>
        </template>
    </span>
@endif
