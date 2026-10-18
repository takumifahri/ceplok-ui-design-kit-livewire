@php
    use CeplokUi\DesignKit\Enums\SearchApplication;
    use CeplokUi\DesignKit\Enums\SearchPlatform;
    use CeplokUi\DesignKit\Enums\SearchTrigger;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Options;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\Support\Js;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Search Form — kolom pencarian dengan tombol cari.

    Tanpa `categories` ia satu kotak berisi ikon kaca pembesar, isian, dan
    tombol berlabel. Dengan `categories` ia tiga ruas menyatu: dropdown
    kategori, isian, lalu tombol ikon. Daftar kategorinya panel Dropdown, sama
    dengan daftar pilihan Select.

    Event `search` (`detail.value`, `detail.category`), padanan onSearch di
    React, dikirim saat Enter ditekan di dalam field atau tombol cari ditekan;
    dengan `trigger="change"`/`"auto"` juga setiap isian berubah (jeda
    `debounce`, bawaan 300ms) dan setiap kategori diganti. Mengganti kategori
    juga memicu `category-change` (`detail.value`). Kedua event dikirim dari
    elemen input-nya, mis. `wire:search="cari($event.detail.value)"`.

    Atribut lain (`name`, `wire:model`, `placeholder`, …) diteruskan ke
    `<input type="search">`-nya; `class` dan `wire:key` ke pembungkus terluar.
    Kategori awalnya lewat `category`; `category-attributes` diteruskan ke
    `<select>` kategorinya, mis. `['wire:model.live' => 'kategori']`.

    Pembungkusnya `role="search"`, bukan `<form>`: kolom pencarian sering
    dipasang di dalam formulir lain, dan form bersarang tidak sah.
--}}
@props([
    'label' => null,
    'helperText' => null,
    'buttonLabel' => 'Cari',
    'trigger' => 'enter',
    'debounce' => 300,
    'showButton' => null,
    'platform' => 'default',
    'application' => 'default',
    'categories' => null,
    'categoryPlaceholder' => 'Kategori',
    'category' => null,
    'categoryAttributes' => [],
    'darkMode' => false,
])

@php
    $searchTrigger = Prop::oneOf(SearchTrigger::class, $trigger, SearchTrigger::Enter, 'search', 'trigger');
    $debounce = Prop::int($debounce, 300, 'search', 'debounce', min: 0);
    $platform = Prop::oneOf(SearchPlatform::class, $platform, SearchPlatform::Default, 'search', 'platform');
    $application = Prop::oneOf(SearchApplication::class, $application, SearchApplication::Default, 'search', 'application');
    $darkMode = Prop::bool($darkMode, false, 'search', 'dark-mode');
    $hasButton = $showButton === null ? $platform !== 'compact' : Prop::bool($showButton, true, 'search', 'show-button');

    $id = FieldId::for($attributes, 'search');
    $helperId = $id.'-helper';
    $disabled = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true);
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);

    // Tinggi kotak, ukuran ikon, ukuran teks, dan ukuran tombol per platform.
    // Tombolnya bukan Button: aksen purple Button purple-700 sedangkan tombol
    // cari purple-500, dan jarak sampingnya 13px, bukan px-4.
    $platforms = [
        'default' => ['field' => 'h-13.5', 'icon' => 'size-4.5', 'text' => 'text-base', 'button' => 'h-[38px] px-[13px] text-sm'],
        'mobile' => ['field' => 'h-12.5', 'icon' => 'size-3.5', 'text' => 'text-sm', 'button' => 'h-[34px] px-[13px] text-xs'],
        'compact' => ['field' => 'h-[37px]', 'icon' => 'size-3.5', 'text' => 'text-sm', 'button' => 'h-[25px] px-[10px] text-xs'],
    ];

    // `focus` dipakai varian polos yang garisnya di pembungkus, `groupFocus`
    // varian kategori yang garisnya tersebar di dua ruas.
    $accents = [
        'default' => ['solid' => 'bg-primary-700 hover:bg-primary-800 focus-visible:outline-primary-700', 'focus' => 'focus-within:border-primary-500', 'groupFocus' => 'group-focus-within:border-primary-500'],
        'purple' => ['solid' => 'bg-purple-500 hover:bg-purple-600 focus-visible:outline-purple-500', 'focus' => 'focus-within:border-purple-500', 'groupFocus' => 'group-focus-within:border-purple-500'],
    ];
    $size = $platforms[$platform];
    $accent = $accents[$application];

    // Kategori: placeholder ikut jadi baris pilihan biasa, seperti <option value=""> pertama.
    $categoryList = $categories === null ? [] : collect(Options::fromArray($categories))->flatMap(fn (array $group) => $group['options'])->values()->all();
    $withCategory = $categoryList !== [];
    $categoryBag = new ComponentAttributeBag(collect($categoryAttributes)->all());
    $categoryValue = (string) ($category ?? Bound::text($categoryBag) ?? '');
    $categoryOptions = [['value' => '', 'label' => (string) $categoryPlaceholder, 'disabled' => false], ...$categoryList];
    $categoryLabel = collect($categoryOptions)->firstWhere('value', $categoryValue)['label'] ?? $categoryPlaceholder;
    $categoryItems = array_map(fn (array $option) => [
        'label' => $option['label'],
        'disabled' => $option['disabled'],
        'selected' => $option['value'] === $categoryValue,
        'selected-when' => 'ceplokValue === '.Js::from($option['value']),
        'attributes' => ['x-on:click' => 'ceplokChoose('.Js::from($option['value']).')'],
    ], $categoryOptions);

    $rootClass = (string) $attributes->get('class', '');
    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'type'])
        ->merge([
            'aria-describedby' => $hasHelper ? $helperId : null,
            'value' => $attributes->has('value') ? null : Bound::text($attributes),
        ])
        // Safari dan Chrome menambahkan tombol silang sendiri pada type="search";
        // ia bukan bagian dari rancangan ini dan tidak bisa diberi gaya, jadi dimatikan.
        ->class('min-w-0 flex-1 bg-transparent outline-none text-content placeholder:text-content-muted disabled:cursor-not-allowed disabled:text-content-disabled [&::-webkit-search-cancel-button]:appearance-none');
@endphp

<div {{ $root->class(['w-full' => ! str_contains($rootClass, 'w-')]) }} x-data="ceplokSearch(@js($searchTrigger), @js($debounce))">
    @if ($hasLabel)
        <label for="{{ $id }}" class="mb-2 block text-sm font-bold text-content">{{ $label }}</label>
    @endif

    @if ($withCategory)
        {{-- Tinggi 39px persis: pada tiga ruas yang bersebelahan selisih 1px langsung terbaca sebagai garis yang tidak sejajar. --}}
        <div role="search" class="group flex h-[39px] items-stretch">
            <div
                @class([
                    'relative flex shrink-0 items-center rounded-l-lg border transition-colors',
                    'border-field-border bg-search-category-surface',
                    $accent['groupFocus'] => ! $disabled,
                ])
                x-data="ceplokSelect(@js(Bound::has($categoryBag) ? null : $categoryValue), @js((string) $categoryPlaceholder))"
            >
                {{--
                    Pembawa nilai saja, tapi tetap di alur: lebar ruas kategori
                    ditentukan pilihan terpanjangnya, bukan yang sedang aktif.
                --}}
                <select
                    @disabled($disabled)
                    {{ $categoryBag->merge(['aria-hidden' => 'true', 'tabindex' => '-1'])->class('pointer-events-none h-full appearance-none pr-10 pl-5 text-sm opacity-0') }}
                >
                    @foreach ($categoryOptions as $option)
                        {{-- wire:key: lihat Select — opsi yang diganti server dipasang sebagai elemen baru. --}}
                        <option wire:key="{{ $option['value'] }}" value="{{ $option['value'] }}" @disabled($option['disabled'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>

                <x-ceplok::dropdown attached :content-label="$categoryPlaceholder" :items="$categoryItems" :dark-mode="$darkMode">
                    <x-slot:trigger
                        raw
                        :disabled="$disabled"
                        :aria-label="$categoryPlaceholder"
                        class="absolute inset-0 flex items-center pr-10 pl-5 text-left text-sm outline-none disabled:cursor-not-allowed text-content disabled:text-content-disabled"
                    >
                        <span class="truncate" x-text="ceplokLabel">{{ $categoryLabel }}</span>
                    </x-slot:trigger>
                </x-ceplok::dropdown>

                <span aria-hidden="true" @class(['pointer-events-none absolute right-5 flex items-center', $disabled ? 'text-content-disabled' : 'text-content'])>
                    <svg class="size-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 4.5 6 8.5l4-4" />
                    </svg>
                </span>
            </div>

            <div
                @class([
                    'flex min-w-0 flex-1 items-center border-y px-2.5 text-sm transition-colors',
                    'border-field-border bg-search-field-surface',
                    $accent['groupFocus'] => ! $disabled,
                    'rounded-r-lg border-r' => ! $hasButton,
                ])
            >
                <input id="{{ $id }}" type="search" {{ $input }} />
            </div>

            @if ($hasButton)
                <button
                    type="button"
                    x-on:click="ceplokSubmit()"
                    @disabled($disabled)
                    aria-label="{{ $buttonLabel }}"
                    @class([
                        'flex w-11 shrink-0 items-center justify-center rounded-r-lg text-white transition-colors',
                        'focus-visible:outline-2 focus-visible:outline-offset-2',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        $accent['solid'],
                    ])
                >
                    {{ svg('ceplok-o-search', 'size-4') }}
                </button>
            @endif
        </div>
    @else
        <div
            role="search"
            @class([
                'flex items-center gap-2 rounded-lg border px-2.5 transition-colors',
                $disabled ? 'border-field-border bg-field-disabled' : 'border-field-border bg-search-field-surface',
                $size['field'],
                $size['text'],
                $accent['focus'] => ! $disabled,
            ])
        >
            <span @class(['flex shrink-0 items-center', $disabled ? 'text-content-disabled' : 'text-content-muted'])>
                {{ svg('ceplok-o-search', $size['icon']) }}
            </span>

            <input id="{{ $id }}" type="search" {{ $input }} />

            @if ($hasButton)
                <button
                    type="button"
                    x-on:click="ceplokSubmit()"
                    @disabled($disabled)
                    @class([
                        'shrink-0 rounded-lg font-medium text-white transition-colors',
                        'focus-visible:outline-2 focus-visible:outline-offset-2',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        $size['button'],
                        $accent['solid'],
                    ])
                >{{ $buttonLabel }}</button>
            @endif
        </div>
    @endif

    @if ($hasHelper)
        <p id="{{ $helperId }}" @class(['mt-2 text-sm', $disabled ? 'text-content-disabled' : 'text-content-muted'])>{{ $helperText }}</p>
    @endif
</div>
