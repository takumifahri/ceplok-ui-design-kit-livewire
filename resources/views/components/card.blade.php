@php
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Card — semua varian (dengan/tanpa gambar, tombol, tautan) terbentuk dari
    bagian yang sama; cukup hilangkan bagian yang tidak dipakai. `title` dan
    `description` bisa berupa prop atau slot; tombol masuk ke slot `actions`,
    dan isi lain ke slot bawaan.

    @container: ukuran teks dan jarak mengikuti lebar kartu, bukan lebar layar.

    Warnanya hanya dari token semantik (`surface`, `content`, `brand`), jadi
    tema gelap cukup mengganti nilai token lewat `data-theme`. `dark-mode`
    sekadar memasang `data-theme="dark"`; tanpa prop ini kartu ikut tema
    pembungkusnya. Tombol di `actions` tidak ikut diubah — warnanya tetap
    urusan halaman pemakai.
--}}
@props([
    'image' => null,
    'imageAlt' => '',
    'title' => null,
    'description' => null,
    'href' => null,
    'linkLabel' => null,
    'actions' => null,
    'darkMode' => false,
])

@php
    $darkMode = Prop::bool($darkMode, false, 'card', 'dark-mode');
@endphp

<article
    {{ $attributes
        ->class('@container flex flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-soft')
        ->merge($darkMode ? ['data-theme' => 'dark'] : []) }}
>
    @if (filled($image))
        {{-- Di tema gelap gambar sedikit diredupkan supaya tidak menyilaukan. --}}
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="aspect-[5/4] w-full object-cover in-data-[theme=dark]:brightness-90 @xs:aspect-video"
        />
    @endif

    <div class="flex flex-1 flex-col gap-3 p-4 @xs:p-6">
        @if (Content::filled($title))
            <h3 class="text-base leading-snug font-bold text-content @xs:text-xl">{{ $title }}</h3>
        @endif

        @if (Content::filled($description))
            <p class="text-sm leading-6 text-content-subtle">{{ $description }}</p>
        @endif

        @if (filled($href))
            <a
                href="{{ $href }}"
                class="inline-flex w-fit items-center gap-1.5 rounded text-sm font-bold text-brand transition-colors hover:text-brand-hover hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
            >
                {{ $linkLabel ?? 'Selengkapnya' }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M14 4h6v6m0-6-8.5 8.5M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"
                    />
                </svg>
            </a>
        @endif

        @if (Content::filled($actions))
            <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
        @endif

        {{ $slot }}
    </div>
</article>
