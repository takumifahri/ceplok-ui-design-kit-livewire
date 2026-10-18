@php
    use CeplokUi\DesignKit\Enums\DropdownItemTone;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Dropdown — panel yang dibuka dari sebuah tombol.

    Tombolnya slot `trigger`: dirender sebagai Button kit dengan atribut slot
    sebagai props-nya (`<x-slot:trigger variant="outline">Aksi</x-slot:trigger>`),
    atau sebagai `<button>` polos dengan `raw`. Dropdown yang merendernya,
    supaya atribut Popover dan ARIA-nya ikut dari server dan bertahan saat
    Livewire merender ulang.

    Isinya `items` atau `groups` (`id`, `label`, `separator`, `items`). Tiap
    item berisi `label`, opsional `description`, `icon` (markup atau nama ikon),
    `href` (dirender sebagai tautan), `tone` (`default` | `danger`), `disabled`,
    dan `attributes` untuk tombolnya, mis. `['wire:click' => 'hapus(3)']`.
    Menekan sebuah baris menutup panel. Tanpa items dan groups, slot bawaan
    yang menjadi isi panel.

    Begitu satu item memakai `selected`, panel menjadi daftar pilihan
    (`listbox`) yang ditelusuri dengan panah; semua item lain harus memakainya
    juga. `selected-when` (ekspresi Alpine) membuat `aria-selected`-nya ikut
    berganti tanpa render ulang server.

    `attached` menempelkan panel pada tombolnya, seperti daftar Select;
    `content-label` menamai daftar pilihan bagi pembaca layar; `content-class`
    menambah class pada panel; `dark-mode` memakai panel gelap. Atribut lain
    ke pembungkus terluar.
--}}
@props([
    'items' => null,
    'groups' => null,
    'attached' => false,
    'contentLabel' => null,
    'contentClass' => null,
    'panelId' => null,
    'darkMode' => false,
    'trigger',
])

@php
    $attached = Prop::bool($attached, false, 'dropdown', 'attached');
    $darkMode = Prop::bool($darkMode, false, 'dropdown', 'dark-mode');
    $panelId ??= filled($attributes->get('id')) ? $attributes->get('id').'-panel' : FieldId::for($attributes, 'dropdown');

    $list = collect($groups ?? ($items !== null ? [['id' => 'utama', 'items' => $items]] : []))
        ->map(fn ($group) => [
            'label' => filled($group['label'] ?? null) ? (string) $group['label'] : null,
            'separator' => ! empty($group['separator']),
            'items' => collect($group['items'] ?? [])->values()->all(),
        ])
        ->all();
    $allItems = collect($list)->flatMap(fn (array $group) => $group['items']);
    $hasList = $list !== [];
    $selection = $allItems->contains(fn ($item) => array_key_exists('selected', $item));

    if ($selection && $allItems->contains(fn ($item) => ! array_key_exists('selected', $item) || isset($item['href']))) {
        Prop::violation('<x-ceplok::dropdown> tidak bisa mencampur baris aksi dan baris pilihan. Begitu satu item memakai `selected`, semua item menjadi pilihan (listbox); aksi tombol atau tautan, termasuk item ber-`href`, harus di Dropdown terpisah.');
    }

    // Satu kelompok tanpa judul dan tanpa pemisah tidak perlu pembungkus: pada
    // daftar pilihan `option` harus menjadi anak langsung `listbox`.
    $plain = count($list) === 1 && $list[0]['label'] === null && ! $list[0]['separator'];

    $tones = [
        'light' => ['default' => 'text-gray-700 hover:bg-gray-100', 'danger' => 'text-red-600 hover:bg-red-50'],
        'dark' => ['default' => 'text-gray-300 hover:bg-gray-700', 'danger' => 'text-red-500 hover:bg-gray-700'],
    ];
    $theme = $darkMode ? 'dark' : 'light';
    $base = 'flex w-full items-center gap-3 rounded-md px-4 py-2 text-left text-sm leading-normal font-medium transition-colors';

    $triggerAttributes = ($trigger->attributes ?? new ComponentAttributeBag)->merge([
        'popovertarget' => $panelId,
        'popovertargetaction' => 'toggle',
        'aria-expanded' => 'false',
        'x-bind:aria-expanded' => "ceplokOpen ? 'true' : 'false'",
    ] + ($selection ? ['aria-haspopup' => 'listbox', 'aria-controls' => $panelId] : []));
    $raw = $triggerAttributes->has('raw');
@endphp

<div {{ $attributes->class([$attached ? 'contents' : 'relative inline-block']) }} x-data="ceplokDropdown(@js($attached))">
    @if ($raw)
        <button {{ $triggerAttributes->except('raw')->merge(['type' => 'button']) }}>{{ $trigger }}</button>
    @else
        <x-ceplok::button :attributes="$triggerAttributes">{{ $trigger }}</x-ceplok::button>
    @endif

    <div
        id="{{ $panelId }}"
        popover="auto"
        @if ($selection) role="listbox" @if (filled($contentLabel)) aria-label="{{ $contentLabel }}" @endif @endif
        @class([
            'fixed inset-auto max-w-[calc(100vw-1rem)] overflow-x-hidden rounded-lg shadow-md [overflow-wrap:anywhere]',
            $darkMode ? 'bg-gray-800 text-gray-300' : 'bg-surface text-content',
            $attached ? 'm-0 max-h-72 overflow-y-auto overscroll-contain' : 'mt-2 mr-0 mb-0 ml-0 w-max min-w-[min(anchor-size(width),calc(100vw-1rem))] [position-area:bottom_center]',
            'py-1' => $hasList,
            $contentClass,
        ])
    >
        @if (! $hasList)
            {{ $slot }}
        @else
            @foreach ($list as $group)
                @if (! $plain)
                    <div @if ($selection) role="group" @if ($group['label'] !== null) aria-label="{{ $group['label'] }}" @endif @endif>
                @endif

                @if ($group['separator'])
                    <hr aria-hidden="true" @class(['my-1 border-0 border-t', $darkMode ? 'border-gray-700' : 'border-border'])>
                @endif

                @if ($group['label'] !== null)
                    {{-- Pada daftar pilihan kelompoknya sudah bernama lewat aria-label, jadi judul ini disembunyikan dari pembaca layar. --}}
                    <p @if ($selection) aria-hidden="true" @endif class="px-4 pt-2 pb-1 text-caption font-bold tracking-wide text-gray-400 uppercase">{{ $group['label'] }}</p>
                @endif

                @foreach ($group['items'] as $item)
                    @php
                        $tone = Prop::oneOf(DropdownItemTone::class, $item['tone'] ?? 'default', DropdownItemTone::Default, 'dropdown', 'items.tone');
                        $disabled = ! empty($item['disabled']);
                        $icon = $item['icon'] ?? null;
                        $description = $item['description'] ?? null;
                        $extra = new ComponentAttributeBag($item['attributes'] ?? []);
                    @endphp

                    @php ob_start(); @endphp
                    @if (Content::filled($icon))
                        {{-- Entri daftar, bukan kunci bersyarat: di tampilan terang kedua warnanya sama-sama text-gray-500, dan kunci array yang sama saling menimpa. --}}
                        <span
                            aria-hidden="true"
                            @class([
                                'flex shrink-0 items-center [&_svg]:size-3.5',
                                $tone === 'default' ? ($darkMode ? 'text-gray-300' : 'text-gray-500') : null,
                                $disabled && $darkMode ? 'text-gray-500' : null,
                            ])
                        >{!! Content::icon($icon) !!}</span>
                    @endif
                    @if (Content::filled($description))
                        <span class="min-w-0 flex-1">
                            <span class="block">{{ $item['label'] ?? '' }}</span>
                            <span
                                data-keterangan=""
                                @class(['mt-0.5 block text-xs font-normal', $darkMode ? 'text-gray-400' : 'text-gray-500', 'text-gray-500' => $disabled && $darkMode])
                            >{{ $description }}</span>
                        </span>
                    @else
                        {{ $item['label'] ?? '' }}
                    @endif
                    @php $content = ob_get_clean(); @endphp

                    @if ($selection)
                        {{--
                            Baris daftar pilihan adalah `option`, bukan tombol. Rupanya
                            sengaja tidak dibedakan dari baris menu: yang terpilih ditandai
                            aria-selected dan menerima fokus saat panel dibuka. Atribut
                            item yang dimatikan tidak dipasang, supaya aksinya tidak jalan.
                        --}}
                        <div
                            role="option"
                            tabindex="-1"
                            data-baris=""
                            aria-selected="{{ ! empty($item['selected']) ? 'true' : 'false' }}"
                            @if (filled($item['selected-when'] ?? null)) x-bind:aria-selected="({{ $item['selected-when'] }}) ? 'true' : 'false'" @endif
                            @if ($disabled) aria-disabled="true" @endif
                            {{ ($disabled ? new ComponentAttributeBag : $extra)->class([
                                $base,
                                // `select-none` dan kursor panah menyamakan baris ini dengan baris menu yang berupa <button>.
                                'outline-none select-none',
                                $disabled
                                    ? 'cursor-not-allowed opacity-50 '.($tone === 'danger' ? ($darkMode ? 'text-red-500' : 'text-red-600') : ($darkMode ? 'text-gray-500' : 'text-gray-700'))
                                    : 'cursor-default '.$tones[$theme][$tone].' '.($darkMode ? 'focus:bg-gray-700' : 'focus:bg-gray-100'),
                            ]) }}
                        >{!! $content !!}</div>
                    @else
                        @php
                            $row = $extra->class([
                                $base,
                                'focus-visible:outline-2 focus-visible:outline-offset-2',
                                $darkMode ? 'focus-visible:outline-primary-400' : 'focus-visible:outline-primary-600',
                                'disabled:cursor-not-allowed disabled:opacity-50',
                                $tones[$theme][$tone],
                            ]);
                        @endphp
                        {{-- Aksi yang berpindah halaman dirender sebagai tautan; yang dimatikan tetap <button>. --}}
                        @if (filled($item['href'] ?? null) && ! $disabled)
                            <a href="{{ $item['href'] }}" data-baris="" {{ $row }}>{!! $content !!}</a>
                        @else
                            <button type="button" data-baris="" @disabled($disabled) {{ $row }}>{!! $content !!}</button>
                        @endif
                    @endif
                @endforeach

                @if (! $plain)
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>
