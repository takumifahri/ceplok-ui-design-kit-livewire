@php
    use CeplokUi\DesignKit\Enums\PaginationTheme;
    use CeplokUi\DesignKit\Enums\TableActionIconSize;
    use CeplokUi\DesignKit\Enums\TableActionRadius;
    use CeplokUi\DesignKit\Enums\TableActionTheme;
    use CeplokUi\DesignKit\Enums\TableActionTone;
    use CeplokUi\DesignKit\Enums\TableActionVariant;
    use CeplokUi\DesignKit\Enums\TableAlign;
    use CeplokUi\DesignKit\Enums\TableSize;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\ClassToggle;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
    use CeplokUi\DesignKit\Support\Table;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Enumerable;
    use Illuminate\Support\HtmlString;
    use Illuminate\Support\Js;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Table — tabel data dengan toolbar, pengurutan, seleksi baris, dan
    pagination. Port Table di kit React: markup dan class-nya sama, sedangkan
    pembagian kerjanya mengikuti Livewire.

    Server yang merender baris. Urutan (`sort`) dan halaman (`pagination.page`)
    datang dari server; menekan judul kolom atau tombol halaman hanya mengirim
    event `sort-change` (`detail.sort`), `page-change` (`detail.page`), dan
    `page-size-change` (`detail.pageSize`), yang ditangkap komponen Livewire:

        <x-ceplok::table :columns="$columns" :data="$rows" row-key="id"
            :sort="$sort" :pagination="['page' => $page]"
            wire:sort-change="$set('sort', $event.detail.sort)"
            wire:page-change="$set('page', $event.detail.page)" />

    Tanpa `manual`, Table mengurutkan dan memotong `data` sendiri sesuai `sort`
    dan halaman itu, seperti bawaan React. Dengan `manual` — atau bila `data`
    berupa LengthAwarePaginator, yang otomatis manual — `data` dianggap sudah
    terurut dan hanya berisi baris halaman aktif.

    Yang murni tampilan diurus Alpine di peramban (ceplokTable): baris yang
    dicentang dan kolom yang disembunyikan lewat Filter Data. `wire:model` di
    tag Table mengikat daftar kunci baris yang dicentang ke properti array.

    Kolom berupa array (`key`, `header`, `cell`, `actions`, `image`, `file`,
    `children`, `sortable`, `sort-value`, `sort-fn`, `sort-directions`,
    `sort-resettable`, `sort-icon`, `align`, `header-align`, `emphasis`,
    `width`, `min-width`, `hideable`, `wrap`) — padanan TableColumn dengan nama
    kebab-case. Nilai per baris berupa closure, mis.
    `'cell' => fn ($row) => view('badge-status', ['row' => $row])`; aksi
    baris memakai `attributes` (mis. `wire:click`) sebagai ganti onClick.

    Beda dari React, karena urutan dan halaman milik server:
    - tidak ada `default-sort` dan `pagination.default-page`; nilai awalnya
      adalah nilai awal properti Livewire;
    - kolom yang disembunyikan tetap ada di markup (x-show), jadi kolom
      menempel (`sticky`) dan `colspan` dihitung dari susunan awal;
    - `filter.hidden-columns` terkendali diganti `default-hidden-columns`
      ditambah event `hidden-columns-change`.

    Atribut lain ke pembungkus terluar.
--}}
@props([
    'columns' => null,
    'data' => [],
    'rowKey',
    'size' => 'normal',
    'sticky' => null,
    'search' => null,
    'filter' => null,
    'actions' => null,
    'selectable' => false,
    'selectedKeys' => null,
    'defaultSelectedKeys' => null,
    'sort' => null,
    'sortIcon' => null,
    'headerAlign' => null,
    'pagination' => null,
    'manual' => false,
    'striped' => false,
    'loading' => false,
    'emptyText' => 'Tidak ada data',
])

@php
    $size = Prop::oneOf(TableSize::class, $size, TableSize::Normal, 'table', 'size');
    $selectable = Prop::bool($selectable, false, 'table', 'selectable');
    $manual = Prop::bool($manual, false, 'table', 'manual');
    $striped = Prop::bool($striped, false, 'table', 'striped');
    $loading = Prop::bool($loading, false, 'table', 'loading');
    $headerAlign = $headerAlign === null ? null : Prop::oneOf(TableAlign::class, $headerAlign, TableAlign::Left, 'table', 'header-align');
    $stickyStart = (bool) data_get($sticky, 'start', false);
    $stickyEnd = (bool) data_get($sticky, 'end', false);
    $id = FieldId::for($attributes, 'table');

    // Baris: array biasa, Collection, atau paginator Laravel. Paginator
    // membawa halaman, jumlah per halaman, dan totalnya sendiri.
    $paginator = $data instanceof LengthAwarePaginator ? $data : null;
    $rows = match (true) {
        $paginator !== null => array_values($paginator->items()),
        $data instanceof Enumerable => $data->values()->all(),
        is_array($data) => array_values($data),
        is_iterable($data) => iterator_to_array($data, false),
        default => [],
    };
    $manual = $manual || $paginator !== null;

    $tree = Table::columns($columns, $rows);
    $leaves = Table::leaves($tree);
    $owners = Table::leafOwners($tree);
    $headRows = Table::headRows($tree);
    $sort = Table::sortState($sort);

    // Pagination. `true` atau array (boleh kosong) menyalakannya.
    $paginate = $paginator !== null || (is_array($pagination) ? true : Prop::bool($pagination ?? false, false, 'table', 'pagination'));
    $pg = is_array($pagination) ? $pagination : [];
    $pageSize = Prop::int($pg['page-size'] ?? $paginator?->perPage() ?? 10, 10, 'table', 'pagination.page-size', min: 1);
    $total = $manual ? (int) ($pg['total'] ?? $paginator?->total() ?? count($rows)) : count($rows);
    $totalPages = max(1, (int) ceil($total / $pageSize));
    // Dijepit supaya halaman tidak menunjuk ke luar data setelah data menyusut, mis. selesai dicari.
    $page = min(Prop::int($pg['page'] ?? $paginator?->currentPage() ?? 1, 1, 'table', 'pagination.page', min: 1), $totalPages);
    $pageTheme = Prop::oneOf(PaginationTheme::class, $pg['theme'] ?? 'primary', PaginationTheme::Primary, 'table', 'pagination.theme');
    $pageSizeOptions = $pg['page-size-options'] ?? null;

    $sorted = $manual ? $rows : Table::sort($rows, $leaves, $sort);
    $visibleRows = $manual || ! $paginate ? $sorted : array_slice($sorted, ($page - 1) * $pageSize, $pageSize);
    $from = $total === 0 ? 0 : ($page - 1) * $pageSize + 1;
    $to = min($page * $pageSize, $total);

    // Kolom tersembunyi: hanya kolom teratas (grup dihitung satu).
    $filterOn = $filter !== null && $filter !== false;
    $fc = is_array($filter) ? $filter : [];
    $topKeys = array_map(fn (array $column) => $column['key'], $tree);
    $hidden = $filterOn ? array_values(array_intersect($topKeys, $fc['default-hidden-columns'] ?? [])) : [];
    $visibleTops = array_values(array_diff($topKeys, $hidden));
    $isHidden = fn (string $key) => in_array($key, $hidden, true);
    // x-show selalu dipasang, juga tanpa Filter Data (`hidden` lalu kosong).
    // Morph Livewire tidak memperbarui atribut elemen yang keadaan x-show-nya
    // berubah, jadi x-show yang baru muncul saat filter dinyalakan belakangan
    // tidak akan pernah terpasang di judul dan sel yang sudah ada.
    $show = fn (string $owner) => new ComponentAttributeBag(
        ['x-show' => '! isHidden('.Js::from($owner).')'] + ($isHidden($owner) ? ['style' => 'display: none'] : []),
    );

    // Seleksi: properti wire:model bila diikat, lalu selected-keys, lalu default-selected-keys.
    $bound = Bound::get($attributes);
    $selected = collect(is_iterable($bound) ? $bound : ($selectedKeys ?? $defaultSelectedKeys ?? []))->values()->all();
    $selectedText = array_map(fn ($key) => (string) $key, $selected);
    $isSelected = fn (int|string $key) => in_array((string) $key, $selectedText, true);
    $pageKeys = array_map(fn ($row) => Table::key($row, $rowKey), $visibleRows);
    $pageKeysJs = Js::from($pageKeys);
    $pageSelectedCount = count(array_filter($pageKeys, $isSelected));
    $allPageSelected = $pageKeys !== [] && $pageSelectedCount === count($pageKeys);
    $somePageSelected = $pageSelectedCount > 1 && ! $allPageSelected;

    /*
     * Dua rupa dari desain. `normal`: header abu-abu berhuruf kapital dan garis
     * antarbaris. `compact`: header putih tanpa garis atas, baris setinggi 64px
     * (`h-16` di sel tabel berlaku sebagai tinggi minimum), teks sel gelap, dan
     * gambar kecil persegi 32px.
     */
    $sizes = [
        'normal' => [
            'head' => 'px-4 py-3',
            'cell' => 'px-4 py-4',
            'headBg' => 'bg-gray-50',
            'headText' => 'text-xs uppercase',
            'border' => 'border-gray-200',
            'headTop' => 'border-t-gray-200',
            'cellText' => null,
            'thumb' => null,
            'check' => 'default',
        ],
        'compact' => [
            'head' => 'h-16 px-4 py-2',
            'cell' => 'h-16 px-4 py-2',
            'headBg' => 'bg-white',
            'headText' => 'text-sm',
            'border' => 'border-gray-200',
            'headTop' => 'border-t-transparent',
            'cellText' => 'text-gray-900',
            'thumb' => 'size-8 rounded-md',
            'check' => 'mobile',
        ],
    ];
    $density = $sizes[$size];

    // Latar baris dan sel menempel, dengan dan tanpa zebra.
    $bg = $striped
        ? ['row' => 'even:bg-gray-50 hover:bg-gray-100', 'cell' => 'bg-white group-even:bg-gray-50 group-hover:bg-gray-100']
        : ['row' => 'hover:bg-gray-50', 'cell' => 'bg-white group-hover:bg-gray-50'];

    $aligns = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'];

    // Letak isi tombol sort per posisi judul. Rata kiri: teks di ujung kiri, ikon
    // di ujung kanan kolom, supaya ikon di semua kolom berbaris rapi.
    $sortJustify = ['left' => 'justify-between', 'center' => 'justify-center', 'right' => 'justify-end'];

    // Ukuran tombol aksi per `icon-size`: kotak untuk tombol ikon saja, tinggi
    // dan padding untuk tombol berjudul, serta ukuran ikonnya. `base` mengikuti desain.
    $actionSizes = [
        's' => ['iconOnly' => 'size-5', 'labeled' => 'h-5 gap-1 px-1.5 text-xs', 'icon' => 'size-3.5'],
        'base' => ['iconOnly' => 'size-6', 'labeled' => 'h-6 gap-1.5 px-2 text-xs', 'icon' => 'size-4'],
        'l' => ['iconOnly' => 'size-8', 'labeled' => 'h-8 gap-2 px-3 text-sm', 'icon' => 'size-5'],
        'xl' => ['iconOnly' => 'size-10', 'labeled' => 'h-10 gap-2 px-4 text-base', 'icon' => 'size-6'],
    ];
    $actionRadii = ['none' => 'rounded-none', 's' => 'rounded-xs', 'base' => 'rounded', 'l' => 'rounded-lg', 'full' => 'rounded-full'];

    // Warna tombol aksi baris, terpisah dari Button supaya Table bisa diatur sendiri.
    $actionColors = [
        'primary' => [
            'light' => ['filled' => 'bg-primary-700 text-white hover:bg-primary-800', 'outline' => 'border border-primary-700 text-primary-700 hover:bg-primary-50'],
            'dark' => ['filled' => 'bg-primary-800 text-white hover:bg-primary-700', 'outline' => 'border border-primary-800 text-primary-800 hover:bg-primary-50'],
        ],
        'green' => [
            'light' => ['filled' => 'bg-green-700 text-white hover:bg-green-800', 'outline' => 'border border-green-700 text-green-700 hover:bg-green-50'],
            'dark' => ['filled' => 'bg-green-800 text-white hover:bg-green-700', 'outline' => 'border border-green-800 text-green-800 hover:bg-green-50'],
        ],
        'gray' => [
            'light' => ['filled' => 'bg-gray-500 text-white hover:bg-gray-700', 'outline' => 'border border-gray-500 text-gray-500 hover:bg-gray-100 hover:text-gray-700'],
            'dark' => ['filled' => 'bg-gray-700 text-white hover:bg-gray-500', 'outline' => 'border border-gray-700 text-gray-700 hover:bg-gray-100 hover:text-gray-900'],
        ],
        'purple' => [
            'light' => ['filled' => 'bg-purple-700 text-white hover:bg-purple-800', 'outline' => 'border border-purple-700 text-purple-700 hover:bg-purple-50'],
            'dark' => ['filled' => 'bg-purple-800 text-white hover:bg-purple-700', 'outline' => 'border border-purple-800 text-purple-800 hover:bg-purple-50'],
        ],
        'orange' => [
            'light' => ['filled' => 'bg-orange-600 text-white hover:bg-orange-700', 'outline' => 'border border-orange-600 text-orange-600 hover:bg-orange-50'],
            'dark' => ['filled' => 'bg-orange-700 text-white hover:bg-orange-800', 'outline' => 'border border-orange-700 text-orange-700 hover:bg-orange-50'],
        ],
        // Kuning mengikuti desain: 400 (#e3a008). Token 700 ke atas sudah cokelat.
        'yellow' => [
            'light' => ['filled' => 'bg-yellow-400 text-white hover:bg-yellow-500', 'outline' => 'border border-yellow-400 text-yellow-500 hover:bg-yellow-50'],
            'dark' => ['filled' => 'bg-yellow-500 text-white hover:bg-yellow-400', 'outline' => 'border border-yellow-500 text-yellow-600 hover:bg-yellow-50'],
        ],
        'red' => [
            'light' => ['filled' => 'bg-red-700 text-white hover:bg-red-800', 'outline' => 'border border-red-700 text-red-700 hover:bg-red-50'],
            'dark' => ['filled' => 'bg-red-800 text-white hover:bg-red-700', 'outline' => 'border border-red-800 text-red-800 hover:bg-red-50'],
        ],
    ];

    $defaultSortIcon = fn (?string $direction) => svg($direction === 'asc' ? 'ceplok-o-chevron-up' : 'ceplok-o-chevron-down', 'size-3.5 shrink-0', ['aria-hidden' => 'true']);

    /*
     * Kelas menempel per posisi. Kolom checkbox selebar `w-12` persis, jadi
     * kolom data pertama cukup digeser `left-12`. Latarnya wajib ada — sel
     * sticky yang transparan memperlihatkan isi yang lewat di bawahnya.
     */
    $leafCount = count($leaves);
    $stickyClass = function (int $index, bool $head) use ($stickyStart, $stickyEnd, $leafCount, $density, $bg, $selectable): ?string {
        $isStart = $stickyStart && $index === 0;
        $isEnd = $stickyEnd && $index === $leafCount - 1 && $leafCount > 1;

        if (! $isStart && ! $isEnd) {
            return null;
        }

        return implode(' ', array_filter([
            'sticky z-10',
            $head ? $density['headBg'] : 'transition-colors '.$bg['cell'],
            $isStart ? ($selectable ? 'left-12' : 'left-0') : null,
            $isStart ? 'shadow-[inset_-1px_0_0_var(--color-gray-200)]' : null,
            $isEnd ? 'right-0 shadow-[inset_1px_0_0_var(--color-gray-200)]' : null,
        ]));
    };
    // Lebar kolom sebagai atribut `style`, atau array kosong bila tidak diatur:
    // `style` bernilai null tidak boleh ikut di-merge ke ComponentAttributeBag.
    $widthStyle = function (array $column): array {
        $style = implode(' ', array_filter([
            filled($column['width'] ?? null) ? 'width: '.$column['width'].';' : null,
            filled($column['min-width'] ?? null) ? 'min-width: '.$column['min-width'].';' : null,
        ]));

        return $style === '' ? [] : ['style' => $style];
    };

    $hasActions = Content::filled($actions);
    $searchOn = $search !== null && $search !== false;
    $hasToolbar = $searchOn || $filterOn || $hasActions;
    $visibleLeafCount = count(array_filter($owners, fn (string $owner) => ! $isHidden($owner)));
    $colspan = max(1, $visibleLeafCount + ($selectable ? 1 : 0));

    // Kotak pencarian: dibuat sendiri, tidak memakai komponen Search, jadi
    // perubahan di Search tidak menggeser tampilan tabel. Semua atribut di
    // `search` diteruskan ke <input>-nya, kecuali `class` ke kotaknya.
    $sc = is_array($search) ? $search : [];
    $searchPlaceholder = $sc['placeholder'] ?? 'Cari Data';
    $searchDisabled = ! empty($sc['disabled']);
    $searchInput = new ComponentAttributeBag(Arr::except($sc, ['class', 'type', 'size']));
    $searchInput = $searchInput->merge([
        'placeholder' => $searchPlaceholder,
        // Tanpa label terlihat, pembaca layar memakai placeholder sebagai nama.
        'aria-label' => $searchPlaceholder,
        'value' => $searchInput->has('value') ? null : Bound::text($searchInput),
    ]);

    // Kelas dengan varian container (`@xl:`, `@lg:`) dirakit di sini, bukan di
    // markup, supaya tidak terbaca sebagai direktif Blade.
    $rootClass = '@container overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm';
    $searchClass = implode(' ', array_filter([
        'flex h-10 w-full items-center gap-2 rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm transition-colors',
        'focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500',
        '@xl:w-auto @xl:min-w-[22rem]',
        $searchDisabled ? 'cursor-not-allowed opacity-60' : null,
        $sc['class'] ?? null,
    ]));
    $footerClass = 'flex flex-col-reverse items-start gap-3 border-t border-gray-200 p-4 @lg:flex-row @lg:items-center @lg:justify-between';
    $pageSizeTriggerClass = 'inline-flex h-8 min-w-[4.5rem] items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white pr-2 pl-3 text-sm text-gray-900 transition-colors hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:opacity-50 @lg:h-10';

    // Checkbox "pilih semua": dibuat sendiri, tidak memakai Checkbox, karena
    // butuh status sebagian terpilih (garis datar). Ukuran dan warnanya
    // disamakan dengan checkbox baris.
    $filledExpression = "allSelected({$pageKeysJs}) || someSelected({$pageKeysJs})";
    $selectAll = (new ClassToggle)
        ->always('peer/all absolute inset-0 size-full cursor-pointer appearance-none rounded border transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:opacity-50')
        ->when($filledExpression, $allPageSelected || $somePageSelected, 'border-primary-700 bg-primary-700', 'border-gray-300 bg-gray-50');
    $selectAllHint = $allPageSelected ? 'Batalkan pilihan di halaman ini' : 'Pilih semua di halaman ini';
    $selectAllHintExpression = "allSelected({$pageKeysJs}) ? 'Batalkan pilihan di halaman ini' : 'Pilih semua di halaman ini'";

    $summary = $pg['summary'] ?? null;
    $summaryContent = $summary instanceof Closure
        ? $summary($from, $to, $total)
        : new HtmlString('Menampilkan <span class="font-bold text-gray-900">'.e($from).'–'.e($to).'</span> dari <span class="font-bold text-gray-900">'.e($total).'</span> data');

    $pageSizeItems = is_array($pageSizeOptions) ? array_map(fn ($n) => [
        'label' => new HtmlString('<span class="flex items-center justify-between gap-3">'.e($n).((int) $n === $pageSize ? svg('ceplok-o-check', 'size-4 text-primary-700', ['aria-hidden' => 'true'])->toHtml() : '').'</span>'),
        'selected' => (int) $n === $pageSize,
        'attributes' => ['x-on:click' => 'setPageSize('.(int) $n.')'],
    ], $pageSizeOptions) : null;
@endphp

<div
    x-data="ceplokTable"
    x-modelable="selected"
    data-selected="{{ json_encode($selected, JSON_THROW_ON_ERROR) }}"
    data-hidden-columns="{{ json_encode($hidden, JSON_THROW_ON_ERROR) }}"
    data-sort="{{ json_encode($sort, JSON_THROW_ON_ERROR) }}"
    {{ $attributes->class($rootClass) }}
>
    @if ($hasToolbar)
        <div class="flex flex-wrap items-center gap-4 p-4">
            @if ($searchOn)
                <label class="{{ $searchClass }}">
                    {{ svg('ceplok-o-search', 'size-4 shrink-0 text-gray-500', ['aria-hidden' => 'true']) }}
                    <input
                        type="search"
                        {{ $searchInput->class([
                            'min-w-0 flex-1 bg-transparent text-gray-900 outline-none placeholder:text-gray-500',
                            'disabled:cursor-not-allowed',
                            // Tombol silang bawaan type="search" di Chrome/Safari bukan bagian desain.
                            '[&::-webkit-search-cancel-button]:appearance-none',
                        ]) }}
                    />
                </label>
            @endif

            @if ($filterOn)
                <x-ceplok::dropdown content-class="w-60">
                    <x-slot:trigger variant="outline" theme="gray" size="s" :left-icon="svg('ceplok-o-adjustments-horizontal')">{{ $fc['label'] ?? 'Filter Data' }}</x-slot:trigger>

                    <div class="p-3">
                        <p class="mb-2 text-xs font-medium text-gray-500">{{ $fc['title'] ?? 'Tampilkan kolom' }}</p>
                        <div class="flex max-h-72 flex-col gap-2 overflow-y-auto">
                            @foreach ($tree as $column)
                                @php
                                    $keyJs = Js::from($column['key']);
                                    $visible = ! $isHidden($column['key']);
                                    $locked = ($column['hideable'] ?? true) === false;
                                    // Kolom terakhir yang tampil tidak bisa dimatikan, supaya tabel tidak pernah kosong tanpa kolom.
                                    $columnToggle = new ComponentAttributeBag([
                                        'wire:key' => 'kolom-'.$column['key'],
                                        'checked' => $visible,
                                        'disabled' => $locked || ($visible && count($visibleTops) === 1),
                                        'x-bind:checked' => "! isHidden({$keyJs})",
                                        'x-on:change' => "toggleColumn({$keyJs}, \$event.target.checked)",
                                    ] + ($locked ? [] : ['x-bind:disabled' => 'isLastVisible('.$keyJs.', '.Js::from($topKeys).')']));
                                @endphp
                                <x-ceplok::checkbox platform="mobile" :label="$column['header']" :attributes="$columnToggle" />
                            @endforeach
                        </div>
                    </div>
                </x-ceplok::dropdown>
            @endif

            @if ($hasActions)
                <div class="ml-auto flex flex-wrap items-center gap-3">{{ $actions }}</div>
            @endif
        </div>
    @endif

    {{--
        Scrollbar tipis yang selalu terlihat, seperti desain mobile: tanda bahwa
        tabel bisa digeser ke samping. Firefox memakai scrollbar-*,
        Chromium/Safari memakai ::-webkit-scrollbar.
    --}}
    <div class="overflow-x-auto [scrollbar-color:var(--color-gray-300)_var(--color-gray-100)] [scrollbar-width:thin] [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:mx-4 [&::-webkit-scrollbar-track]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-300">
        <table class="w-full border-separate border-spacing-0 text-sm" @if ($loading) aria-busy="true" @endif>
            {{--
                Header bisa bertingkat: garis atas milik baris header pertama, garis
                bawah milik judul daun — judul induk tanpa garis bawah, seperti di desain.
            --}}
            <thead>
                @foreach ($headRows as $level => $headRow)
                    <tr>
                        @if ($level === 0 && $selectable)
                            <th
                                scope="col"
                                rowspan="{{ count($headRows) }}"
                                @class([
                                    'w-12 border-y',
                                    $density['border'],
                                    $density['headTop'],
                                    $density['headBg'],
                                    $density['head'],
                                    'sticky left-0 z-10' => $stickyStart,
                                ])
                            >
                                <span @class(['group/all relative inline-flex shrink-0 items-center justify-center', $density['check'] === 'mobile' ? 'size-3.5' : 'size-4'])>
                                    {{-- Sebagian terpilih: klik berikutnya memilih semua. --}}
                                    <input
                                        type="checkbox"
                                        @checked($allPageSelected)
                                        @disabled($loading || $pageKeys === [])
                                        x-bind:checked="allSelected({{ $pageKeysJs }})"
                                        x-on:change="toggleAll({{ $pageKeysJs }}, $event.target.checked)"
                                        aria-checked="{{ $somePageSelected ? 'mixed' : ($allPageSelected ? 'true' : 'false') }}"
                                        x-bind:aria-checked="someSelected({{ $pageKeysJs }}) ? 'mixed' : (allSelected({{ $pageKeysJs }}) ? 'true' : 'false')"
                                        aria-label="{{ $selectAllHint }}"
                                        x-bind:aria-label="{{ $selectAllHintExpression }}"
                                        class="{{ $selectAll->classes() }}"
                                        x-bind:class="{{ $selectAll->binding() }}"
                                    />
                                    <svg
                                        class="pointer-events-none relative size-2.5 text-white"
                                        viewBox="0 0 10 10"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        aria-hidden="true"
                                        x-show="{{ $filledExpression }}"
                                        @if (! $allPageSelected && ! $somePageSelected) style="display: none" @endif
                                    >
                                        <path stroke-linecap="round" d="M2 5h6" x-show="someSelected({{ $pageKeysJs }})" @if (! $somePageSelected) style="display: none" @endif />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 5 3.6 8.2 9 1.8" x-show="! someSelected({{ $pageKeysJs }})" @if ($somePageSelected) style="display: none" @endif />
                                    </svg>
                                    {{--
                                        Tooltip di bawah kotak, bukan di atas: wadah tabel ber-overflow,
                                        jadi tooltip di atas judul akan terpotong. Muncul saat hover dan
                                        saat fokus keyboard; pembaca layar sudah dapat aria-label.
                                    --}}
                                    <span
                                        aria-hidden="true"
                                        x-text="{{ $selectAllHintExpression }}"
                                        class="pointer-events-none absolute top-full left-0 z-20 mt-2 rounded-md bg-gray-900 px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-white normal-case opacity-0 shadow-lg transition-opacity group-hover/all:opacity-100 peer-focus-visible/all:opacity-100 peer-disabled/all:hidden"
                                    >{{ $selectAllHint }}</span>
                                </span>
                            </th>
                        @endif

                        @foreach ($headRow as $cell)
                            @php
                                $headColumn = $cell['column'];
                                $common = [
                                    'font-medium whitespace-nowrap',
                                    $density['headBg'],
                                    $density['headText'],
                                    $density['head'],
                                    $aligns[Table::headAlign($headColumn, $headerAlign)],
                                    $level === 0 ? 'border-t '.$density['headTop'] : null,
                                ];
                            @endphp

                            @if ($cell['group'])
                                <th scope="colgroup" colspan="{{ $cell['colspan'] }}" {{ $show($cell['top'])->class([...$common, 'pb-0 text-gray-500']) }}>{{ $headColumn['header'] }}</th>
                            @else
                                @php
                                    $column = $leaves[$cell['leaf']];
                                    $isSorted = $sort !== null && $sort['key'] === $column['key'];
                                    $renderSortIcon = $column['sort-icon'] ?? $sortIcon ?? $defaultSortIcon;
                                    $headAttributes = $show($cell['top'])->merge([
                                        ...$widthStyle($column),
                                        'aria-sort' => $isSorted ? ($sort['direction'] === 'asc' ? 'ascending' : 'descending') : null,
                                    ]);
                                @endphp
                                <th
                                    scope="col"
                                    rowspan="{{ $cell['rowspan'] }}"
                                    {{ $headAttributes->class([
                                        ...$common,
                                        'border-b',
                                        // Judul yang membentang beberapa baris turun ke bawah, sebaris dengan judul daun di bawah judul induk.
                                        $cell['rowspan'] > 1 ? 'align-bottom' : null,
                                        $density['border'],
                                        $isSorted ? 'text-gray-900' : 'text-gray-500',
                                        $stickyClass($cell['leaf'], true),
                                    ]) }}
                                >
                                    @if (! empty($column['sortable']))
                                        <button
                                            type="button"
                                            x-on:click="toggleSort({{ Js::from($column['key']) }}, {{ Js::from(Table::sortCycle($column)) }})"
                                            @class([
                                                'flex w-full items-center gap-2 transition-colors hover:text-gray-900',
                                                // Rata kiri: ikon sort di ujung kanan judul, di kedua size.
                                                $sortJustify[Table::headAlign($column, $headerAlign)],
                                                'uppercase' => $size !== 'compact',
                                                'rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600',
                                            ])
                                        >{{ $column['header'] }}{{ $renderSortIcon($isSorted ? $sort['direction'] : null) }}</button>
                                    @else
                                        {{ $column['header'] }}
                                    @endif
                                </th>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </thead>

            <tbody class="[&>tr:last-child>td]:border-b-0">
                @if ($loading)
                    <tr>
                        <td colspan="{{ $colspan }}" class="border-b border-gray-200 py-12">
                            <x-ceplok::spinner class="mx-auto" aria-label="Memuat data" />
                        </td>
                    </tr>
                @elseif ($visibleRows === [])
                    <tr>
                        <td colspan="{{ $colspan }}" class="border-b border-gray-200 py-12 text-center text-gray-500">{{ $emptyText }}</td>
                    </tr>
                @else
                    @foreach ($visibleRows as $index => $row)
                        @php
                            $key = $pageKeys[$index];
                            $keyJs = Js::from($key);
                            $rowSelected = $isSelected($key);
                            $cellBorder = 'border-b '.$density['border'];
                        @endphp
                        <tr
                            wire:key="{{ $key }}"
                            @if ($selectable)
                                aria-selected="{{ $rowSelected ? 'true' : 'false' }}"
                                x-bind:aria-selected="isSelected({{ $keyJs }}) ? 'true' : 'false'"
                            @endif
                            class="group transition-colors {{ $bg['row'] }}"
                        >
                            @if ($selectable)
                                <td @class(['w-12', $cellBorder, $density['cell'], 'sticky left-0 z-10 transition-colors '.$bg['cell'] => $stickyStart])>
                                    <x-ceplok::checkbox
                                        :platform="$density['check']"
                                        :attributes="new ComponentAttributeBag([
                                            'checked' => $rowSelected,
                                            'aria-label' => 'Pilih baris '.($index + 1),
                                            'x-bind:checked' => 'isSelected('.$keyJs.')',
                                            'x-on:change' => 'toggleRow('.$keyJs.', $event.target.checked)',
                                        ])"
                                    />
                                </td>
                            @endif

                            @foreach ($leaves as $i => $column)
                                <td
                                    {{ $show($owners[$i])->merge($widthStyle($column))->class([
                                        $cellBorder,
                                        $density['cell'],
                                        $aligns[$column['align'] ?? 'center'],
                                        $density['cellText'] ?? (! empty($column['emphasis']) ? 'text-gray-900' : 'text-gray-500'),
                                        empty($column['wrap']) ? 'whitespace-nowrap' : null,
                                        $stickyClass($i, false),
                                    ]) }}
                                >
                                    @if (isset($column['cell']))
                                        {{ Table::resolve($column['cell'], $row, $index) }}
                                    @elseif (isset($column['actions']))
                                        <span class="inline-flex items-center justify-center gap-1.5">
                                            @foreach ($column['actions'] as $action)
                                                @if (! Table::resolve($action['hidden'] ?? false, $row))
                                                    @php
                                                        $label = (string) Table::resolve($action['label'] ?? '', $row);
                                                        $disabled = (bool) Table::resolve($action['disabled'] ?? false, $row);
                                                        $showLabel = Prop::bool($action['show-label'] ?? false, false, 'table', 'actions.show-label');
                                                        $actionTheme = Prop::oneOf(TableActionTheme::class, $action['theme'] ?? 'primary', TableActionTheme::Primary, 'table', 'actions.theme');
                                                        $actionTone = Prop::oneOf(TableActionTone::class, $action['tone'] ?? 'light', TableActionTone::Light, 'table', 'actions.tone');
                                                        $actionVariant = Prop::oneOf(TableActionVariant::class, $action['variant'] ?? 'filled', TableActionVariant::Filled, 'table', 'actions.variant');
                                                        $actionSize = $actionSizes[Prop::oneOf(TableActionIconSize::class, $action['icon-size'] ?? 'base', TableActionIconSize::Base, 'table', 'actions.icon-size')];
                                                        $actionRadius = $actionRadii[Prop::oneOf(TableActionRadius::class, $action['radius'] ?? 'base', TableActionRadius::Base, 'table', 'actions.radius')];
                                                        $href = isset($action['href']) && ! $disabled ? (string) Table::resolve($action['href'], $row) : null;
                                                        $target = $action['target'] ?? null;
                                                        $actionAttributes = (new ComponentAttributeBag((array) Table::resolve($action['attributes'] ?? [], $row, $index)))
                                                            ->merge(['aria-label' => $showLabel ? null : $label])
                                                            ->class([
                                                                'inline-flex shrink-0 items-center justify-center font-medium whitespace-nowrap',
                                                                'transition-colors duration-200',
                                                                'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400',
                                                                'disabled:pointer-events-none disabled:opacity-50',
                                                                $actionColors[$actionTheme][$actionTone][$actionVariant],
                                                                $showLabel ? $actionSize['labeled'] : $actionSize['iconOnly'],
                                                                $actionRadius,
                                                            ]);
                                                    @endphp

                                                    {{-- `[&>svg]:size-full` supaya ikon yang membawa ukuran sendiri tetap mengikuti `icon-size`. --}}
                                                    @php ob_start(); @endphp
                                                    <span class="flex shrink-0 items-center justify-center [&>svg]:size-full {{ $actionSize['icon'] }}">{!! Content::icon($action['icon']) !!}</span>@if ($showLabel){{ $label }}@endif
                                                    @php $actionContent = trim((string) ob_get_clean()); @endphp

                                                    {{-- <a> tidak bisa dinonaktifkan, jadi tautan yang disabled dirender sebagai <button disabled>. --}}
                                                    @php ob_start(); @endphp
                                                    @if ($href !== null)
                                                        <a href="{{ $href }}" @if ($target) target="{{ $target }}" @endif @if ($target === '_blank') rel="noopener noreferrer" @endif {{ $actionAttributes }}>{!! $actionContent !!}</a>
                                                    @else
                                                        <button type="button" @disabled($disabled) {{ $actionAttributes }}>{!! $actionContent !!}</button>
                                                    @endif
                                                    @php $actionButton = trim((string) ob_get_clean()); @endphp

                                                    @if ($showLabel)
                                                        {!! $actionButton !!}
                                                    @else
                                                        {{--
                                                            Tooltip sendiri, bukan atribut `title`: tooltip bawaan browser baru
                                                            muncul setelah jeda dan di sebagian Chromium Linux tidak muncul sama
                                                            sekali. Hover dipasang di pembungkus supaya tombol disabled — yang
                                                            `pointer-events-none` — tetap menjelaskan dirinya.
                                                        --}}
                                                        <span class="group/action relative inline-flex">
                                                            {!! $actionButton !!}
                                                            <span
                                                                aria-hidden="true"
                                                                class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 -translate-x-1/2 rounded-md bg-gray-900 px-2 py-1 text-xs font-medium whitespace-nowrap text-white normal-case shadow-sm opacity-0 transition-opacity duration-150 group-hover/action:opacity-100 group-has-[:focus-visible]/action:opacity-100"
                                                            >{{ $label }}</span>
                                                        </span>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </span>
                                    @elseif (isset($column['image']))
                                        @php $src = Table::resolve($column['image']['src'] ?? null, $row); @endphp
                                        @if (blank($src))
                                            —
                                        @else
                                            <x-ceplok::table-image
                                                :src="$src"
                                                :alt="isset($column['image']['alt']) ? Table::resolve($column['image']['alt'], $row) : ''"
                                                :caption="isset($column['image']['caption']) ? Table::resolve($column['image']['caption'], $row) : null"
                                                :show-caption="$size !== 'compact'"
                                                :preview="$column['image']['preview'] ?? true"
                                                :thumb-class="$density['thumb']"
                                            />
                                        @endif
                                    @elseif (isset($column['file']))
                                        @php $fileName = Table::resolve($column['file']['name'] ?? null, $row); @endphp
                                        @if (blank($fileName))
                                            —
                                        @else
                                            <x-ceplok::table-file
                                                :name="$fileName"
                                                :href="isset($column['file']['href']) ? Table::resolve($column['file']['href'], $row) : null"
                                                :type="isset($column['file']['type']) ? Table::resolve($column['file']['type'], $row) : null"
                                                :preview="$column['file']['preview'] ?? true"
                                            />
                                        @endif
                                    @else
                                        {{ Table::format(Table::read($row, $column['key'])) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if ($paginate)
        {{-- Kiri hanya info (ringkasan), kanan semua kontrol (jumlah baris + halaman). Sempit: kontrol di atas, ringkasan di bawahnya, rata kiri. --}}
        <div class="{{ $footerClass }}">
            <p class="text-sm text-gray-500">{{ $summaryContent }}</p>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                @if ($pageSizeItems !== null)
                    <div class="inline-flex items-center gap-2 text-sm whitespace-nowrap text-gray-500">
                        <span id="{{ $id }}-page-size-label">Baris per halaman</span>
                        {{--
                            Daftar pilihan memakai Dropdown (mode listbox) supaya rupanya sama
                            dengan Dropdown lain; <select> bawaan tidak bisa diberi gaya. Tinggi,
                            sudut, dan garis tombolnya disamakan dengan Pagination.
                        --}}
                        <x-ceplok::dropdown attached content-label="Baris per halaman" :items="$pageSizeItems">
                            <x-slot:trigger
                                raw
                                :disabled="$loading"
                                :aria-labelledby="$id.'-page-size-label '.$id.'-page-size-value'"
                                :class="$pageSizeTriggerClass"
                            ><span id="{{ $id }}-page-size-value">{{ $pageSize }}</span>{{ svg('ceplok-o-chevron-down', 'size-4 text-gray-500', ['aria-hidden' => 'true']) }}</x-slot:trigger>
                        </x-ceplok::dropdown>
                    </div>
                @endif
                <x-ceplok::pagination :current-page="$page" :total-pages="$totalPages" :theme="$pageTheme" size="responsive" />
            </div>
        </div>
    @endif
</div>
