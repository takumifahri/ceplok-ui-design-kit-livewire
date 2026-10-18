@php
    use CeplokUi\DesignKit\Enums\PaginationSize;
    use CeplokUi\DesignKit\Enums\PaginationTheme;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Pagination — deretan nomor halaman dengan tombol sebelumnya/berikutnya.
    Lebih dari 5 halaman diringkas dengan "...", seperti di kit React.

    Dua cara menyambungkannya:
    - Livewire: `wire:model.live="page"` mengikat halaman aktif ke properti
      komponen (butuh plugin Alpine kit). Setiap klik juga mengirim event
      `page-change` dengan `detail.page`, padanan onPageChange di React.
    - Halaman Blade biasa: `url` membuat setiap halaman menjadi tautan, berupa
      pola dengan `{page}` (mis. `/pengajuan?page={page}`) atau closure
      `fn (int $page) => route('pengajuan', ['page' => $page])`.

    `size`: `base` kotak 40px, `s` kotak 32px untuk layar sempit, dan
    `responsive` yang berganti dari `s` ke `base` saat container terdekat
    ≥ 512px — wajib ada induk ber-`@container`.
--}}
@props([
    'currentPage' => 1,
    'totalPages' => 1,
    'theme' => 'primary',
    'size' => 'base',
    'url' => null,
    'darkMode' => false,
])

@php
    $currentPage = Prop::int($currentPage, 1, 'pagination', 'current-page', min: 1);
    $totalPages = Prop::int($totalPages, 1, 'pagination', 'total-pages', min: 0);
    $theme = Prop::oneOf(PaginationTheme::class, $theme, PaginationTheme::Primary, 'pagination', 'theme');
    $size = Prop::oneOf(PaginationSize::class, $size, PaginationSize::Base, 'pagination', 'size');
    $darkMode = Prop::bool($darkMode, false, 'pagination', 'dark-mode');

    // Tinggi bar, ukuran tiap kotak, dan ikon chevron per ukuran. `s` memakai
    // lebar minimum, bukan tetap, supaya angka tiga digit tidak berdesakan.
    $sizes = [
        'base' => ['nav' => 'h-10', 'cell' => 'h-10 w-10', 'icon' => 'size-7'],
        's' => ['nav' => 'h-8', 'cell' => 'h-8 min-w-8 px-2', 'icon' => 'size-5'],
        'responsive' => ['nav' => 'h-8 @lg:h-10', 'cell' => 'h-8 min-w-8 px-2 @lg:h-10 @lg:min-w-10', 'icon' => 'size-5 @lg:size-7'],
    ];
    $themes = [
        'default' => ['active' => 'bg-gray-50', 'activeText' => 'text-gray-500', 'darkActive' => 'bg-gray-700', 'darkActiveText' => 'text-gray-300'],
        'primary' => ['active' => 'bg-primary-50', 'activeText' => 'text-primary-500', 'darkActive' => 'bg-gray-700', 'darkActiveText' => 'text-primary-500'],
        'purple' => ['active' => 'bg-purple-50', 'activeText' => 'text-purple-500', 'darkActive' => 'bg-gray-700', 'darkActiveText' => 'text-purple-400'],
    ];
    $sizing = $sizes[$size];
    $colors = $themes[$theme];

    // Sama dengan getPaginationPages di kit React.
    if ($totalPages <= 5) {
        $pages = $totalPages === 0 ? [] : range(1, $totalPages);
    } elseif ($currentPage <= 3) {
        $pages = [1, 2, 3, '...', $totalPages];
    } elseif ($currentPage >= $totalPages - 2) {
        $pages = [1, '...', $totalPages - 2, $totalPages - 1, $totalPages];
    } else {
        $pages = [1, '...', $currentPage, '...', $totalPages];
    }

    $href = match (true) {
        $url instanceof Closure => fn (int $page) => (string) $url($page),
        is_string($url) && $url !== '' => fn (int $page) => str_replace('{page}', (string) $page, $url),
        default => null,
    };

    $arrow = [
        'flex shrink-0 items-center justify-center transition-colors',
        $sizing['cell'],
        $darkMode ? 'border-r border-gray-700 text-gray-400 hover:bg-gray-700' : 'border-r border-gray-300 text-gray-500 hover:bg-gray-50',
        'disabled:cursor-not-allowed disabled:opacity-50',
    ];
    // `disabled` sama persis dengan kit React (ujung pertama dan terakhir).
    // Berpindah halaman dibatasi terpisah: di React tombol yang tidak disabled
    // pun tidak berbuat apa-apa bila tujuannya di luar 1..totalPages.
    $previousDisabled = $currentPage === 1;
    $nextDisabled = $currentPage === $totalPages;
    $hasPrevious = $currentPage > 1 && $currentPage - 1 <= $totalPages;
    $hasNext = $currentPage < $totalPages;
@endphp

<nav
    @if ($href === null)
        x-data="ceplokPagination"
        x-modelable="page"
    @endif
    {{ $attributes
        ->class([
            'inline-flex overflow-hidden rounded-lg',
            $sizing['nav'],
            $darkMode ? 'border border-gray-700 bg-gray-800' : 'border border-gray-300 bg-white',
        ])
        ->merge(['aria-label' => 'Pagination']) }}
>
    @if ($href !== null && $hasPrevious)
        <a href="{{ $href($currentPage - 1) }}" aria-label="Previous page" @class($arrow)>
            {{ svg('ceplok-o-chevron-left', $sizing['icon']) }}
        </a>
    @else
        <button
            type="button"
            aria-label="Previous page"
            @disabled($previousDisabled)
            @if ($href === null && $hasPrevious) x-on:click="go({{ $currentPage - 1 }})" @endif
            @class($arrow)
        >
            {{ svg('ceplok-o-chevron-left', $sizing['icon']) }}
        </button>
    @endif

    @foreach ($pages as $page)
        @if ($page === '...')
            <span
                @class([
                    'flex shrink-0 items-center justify-center text-sm',
                    $sizing['cell'],
                    $darkMode ? 'border-r border-gray-700 text-gray-400' : 'border-r border-gray-300 text-gray-500',
                ])
            >...</span>
        @else
            @php
                $active = $page === $currentPage;
                $pageClasses = [
                    'flex shrink-0 items-center justify-center text-sm font-normal transition-colors',
                    $sizing['cell'],
                    $darkMode ? 'border-r border-gray-700 hover:bg-gray-700' : 'border-r border-gray-300 hover:bg-gray-50',
                    ($darkMode ? $colors['darkActive'] : $colors['active']) => $active,
                    $active
                        ? ($darkMode ? $colors['darkActiveText'] : $colors['activeText'])
                        : ($darkMode ? 'text-gray-400' : 'text-gray-500'),
                ];
            @endphp
            @if ($href !== null)
                <a href="{{ $href($page) }}" @if ($active) aria-current="page" @endif @class($pageClasses)>{{ $page }}</a>
            @else
                <button
                    type="button"
                    @if ($active) aria-current="page" @else x-on:click="go({{ $page }})" @endif
                    @class($pageClasses)
                >{{ $page }}</button>
            @endif
        @endif
    @endforeach

    @if ($href !== null && $hasNext)
        <a href="{{ $href($currentPage + 1) }}" aria-label="Next page" @class($arrow)>
            {{ svg('ceplok-o-chevron-right', $sizing['icon']) }}
        </a>
    @else
        <button
            type="button"
            aria-label="Next page"
            @disabled($nextDisabled)
            @if ($href === null && $hasNext) x-on:click="go({{ $currentPage + 1 }})" @endif
            @class($arrow)
        >
            {{ svg('ceplok-o-chevron-right', $sizing['icon']) }}
        </button>
    @endif
</nav>
