@php
    use CeplokUi\DesignKit\Enums\DatepickerPlatform;
    use CeplokUi\DesignKit\Enums\DatepickerType;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\ClassToggle;
    use CeplokUi\DesignKit\Support\Dates;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Datepicker — pemilih tanggal dengan kalender di panel melayang.

    `type`: `single` untuk satu tanggal (Hari ini, Hapus), `period` untuk satu
    kalender dengan pintasan periode (Hari ini, Minggu ini, Bulan ini, Hapus,
    Semua Waktu), dan `multiple` untuk rentang dengan dua kalender — pintasan
    periodenya lewat `shortcuts`. Rentang dipilih dengan dua klik, dan panelnya
    baru tertutup setelah tanggal selesai.

    Nilainya `'YYYY-MM-DD'` pada single dan `['start' => …, 'end' => …]` pada
    period dan multiple, lewat `value` atau `wire:model`/`x-model` di tag
    komponennya. Semua Waktu tanpa `min`/`max` bernilai
    `['start' => null, 'end' => null]`, berbeda dari kosong (null). Setiap
    nilai berganti, event `change` (`detail.value`), padanan onChange, dikirim
    dari elemen terluar. Dengan `name`, tanggal dikirim formulir sebagai
    `YYYY-MM-DD`; pada rentang sebagai `nama[start]` dan `nama[end]`.

    `min` dan `max` menandai awal dan akhir data: tanggal di luarnya tidak bisa
    dipilih, pintasan dipotong ke rentang itu, dan Semua Waktu memilih
    seluruhnya. `platform="mobile"` selebar 325px dengan panel selebar kotak.
    Atribut lain ke elemen terluar.
--}}
@props([
    'type' => 'single',
    'label' => null,
    'placeholder' => 'Pilih Tanggal',
    'endPlaceholder' => null,
    'shortcuts' => false,
    'min' => null,
    'max' => null,
    'platform' => 'default',
    'darkMode' => false,
    'disabled' => false,
    'name' => null,
    'value' => null,
])

@php
    $type = Prop::oneOf(DatepickerType::class, $type, DatepickerType::Single, 'datepicker', 'type');
    $platform = Prop::oneOf(DatepickerPlatform::class, $platform, DatepickerPlatform::Default, 'datepicker', 'platform');
    $shortcuts = Prop::bool($shortcuts, false, 'datepicker', 'shortcuts');
    $darkMode = Prop::bool($darkMode, false, 'datepicker', 'dark-mode');
    $disabled = Prop::bool($disabled, false, 'datepicker', 'disabled');
    $endPlaceholder ??= $placeholder;

    $base = FieldId::for($attributes->merge(['name' => $name]), 'datepicker');
    $labelId = $base.'-label';
    $panelId = $base.'-panel';
    $endId = $base.'-selesai';
    $ariaLabel = $attributes->get('aria-label');

    $mobile = $platform === 'mobile';
    // Dua kotak dan dua kalender — bertumpuk pada mobile.
    $double = $type === 'multiple';
    $stacked = $double && $mobile;
    $periodShortcuts = $type === 'period' || ($double && $shortcuts);

    // Nilai awal: `value`, lalu properti wire:model.
    $range = Dates::range($value ?? Bound::get($attributes));
    $start = $range['start'] ?? null;
    $end = $range['end'] ?? null;
    $allTime = $range !== null && $start === null && $end === null;
    $model = $range === null ? null : ($type === 'single' ? $start : $range);

    $themes = [
        'light' => [
            'label' => 'text-gray-900', 'box' => 'border-gray-300 bg-gray-50', 'icon' => 'text-gray-500', 'text' => 'text-gray-900',
            'placeholder' => 'text-gray-500', 'panel' => 'bg-white', 'title' => 'text-gray-900', 'arrow' => 'text-gray-900 hover:bg-gray-100',
            'weekday' => 'text-gray-500', 'date' => 'text-gray-900', 'outside' => 'text-gray-500',
            // Lebih pudar dari tanggal luar bulan, yang masih bisa dipilih.
            'outOfBounds' => 'text-gray-300', 'hover' => 'hover:bg-gray-100', 'selected' => 'bg-primary-700 text-white', 'between' => 'bg-gray-100',
            'clear' => 'border-gray-200 bg-white text-gray-900 hover:bg-gray-100', 'clearDouble' => 'border-gray-200 bg-white text-gray-900 hover:bg-gray-100',
        ],
        'dark' => [
            'label' => 'text-gray-50', 'box' => 'border-gray-600 bg-gray-700', 'icon' => 'text-gray-400', 'text' => 'text-white',
            'placeholder' => 'text-gray-400', 'panel' => 'bg-gray-700', 'title' => 'text-white', 'arrow' => 'text-white hover:bg-gray-600',
            'weekday' => 'text-gray-400', 'date' => 'text-white', 'outside' => 'text-gray-500',
            'outOfBounds' => 'text-gray-600', 'hover' => 'hover:bg-gray-600', 'selected' => 'bg-primary-600 text-white', 'between' => 'bg-gray-600',
            // Desain multiple gelap menulis Hapus dengan gray-300; single dan period putih.
            'clear' => 'border-gray-500 bg-gray-600 text-white hover:bg-gray-500', 'clearDouble' => 'border-gray-500 bg-gray-600 text-gray-300 hover:bg-gray-500',
        ],
    ];
    $t = $themes[$darkMode ? 'dark' : 'light'];

    // Lebar panel: 7 kolom × 36px, 7 × ±42px, dan dua kalender berdampingan.
    $panelWidth = $mobile ? 'w-81.25' : ['single' => 'w-71', 'period' => 'w-81.25', 'multiple' => 'w-150'][$type];
    // Lebar bawaan komponennya selebar panel, kecuali single yang 325px seperti desainnya.
    $boxWidth = $mobile ? 'max-w-81.25' : ['single' => 'max-w-81.25', 'period' => 'max-w-81.25', 'multiple' => 'max-w-150'][$type];
    // Lebar kisi: selebar kalender, menjorok 8px untuk dua kalender, atau
    // ditahan 7 × 36px di tengah panel mobile.
    $grid = $type === 'period' ? '' : ($mobile ? 'mx-auto max-w-63' : ($double ? 'px-2' : ''));

    // Pada period mobile empat pintasan berbagi satu baris (±61px per tombol),
    // jadi padding sampingnya dilepas.
    $tight = $type === 'period' && $mobile;
    $shortcutGrid = $tight ? 'grid-cols-4 gap-4' : ($stacked ? 'grid-cols-2 gap-x-4 gap-y-3' : ($double ? 'grid-cols-2 gap-4 sm:grid-cols-4' : 'grid-cols-2 gap-4'));

    $text = fn (string $which) => match (true) {
        $allTime => 'Semua Waktu',
        $range === null => null,
        $double => ($which === 'start' ? $start : $end) === null ? null : Dates::write($which === 'start' ? $start : $end),
        $type === 'single' => $start === null ? null : Dates::write($start),
        $start !== null && $end !== null => Dates::writeRange($start, $end),
        default => $start === null ? null : Dates::write($start),
    };

    $boxes = $double
        ? [['start', $base, $placeholder, 'mulai'], ['end', $endId, $endPlaceholder, 'selesai']]
        : [['start', $base, $placeholder, null]];

    $config = [
        'type' => $type,
        'mobile' => $mobile,
        // Yang diikat wire:model/x-model mendapat nilainya dari ikatan itu (x-modelable),
        // jadi x-data tidak berubah setiap Livewire merender ulang dengan nilai baru —
        // perubahan x-data membuat Alpine menginisialisasi ulang datanya, termasuk
        // pemilihan rentang yang sedang berjalan.
        'value' => Bound::has($attributes) ? null : $model,
        'min' => Dates::iso($min),
        'max' => Dates::iso($max),
        'classes' => [
            'selected' => $t['selected'], 'between' => $t['between'], 'date' => $t['date'],
            'outside' => $t['outside'], 'outOfBounds' => $t['outOfBounds'], 'hover' => $t['hover'],
        ],
    ];

    $arrow = '-m-1.5 flex items-center justify-center rounded-lg p-1.5 transition-colors disabled:pointer-events-none disabled:opacity-50 '.$t['arrow'];
    $weekdays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $sides = $double ? ['left' => 'kiri', 'right' => 'kanan'] : ['left' => 'kiri'];
@endphp

<div
    {{ $attributes->except(['id', 'aria-label'])->class(['w-full', $boxWidth]) }}
    x-data="ceplokDatepicker(@js($config))"
    x-modelable="ceplokModel"
>
    @if (filled($label))
        <label for="{{ $base }}" id="{{ $labelId }}" @class(['mb-2 block text-sm font-bold', $t['label']])>{{ $label }}</label>
    @else
        {{-- Tanpa label terlihat, aria-label tetap dipakai sebagai nama kotak dan panelnya. --}}
        <span id="{{ $labelId }}" hidden>{{ $ariaLabel ?? 'Tanggal' }}</span>
    @endif

    {{-- Kedua kotak multiple bisa berteks sama; pembaca layar membedakannya lewat "mulai" dan "selesai". --}}
    <div x-ref="anchor" @if ($double) @class(['grid gap-2', 'grid-cols-2' => ! $stacked]) @endif>
        @foreach ($boxes as [$which, $boxId, $empty, $boxName])
            @php
                $initial = $text($which);
                $words = (new ClassToggle)->always('truncate')->when("ceplokText('{$which}') !== null", $initial !== null, $t['text'], $t['placeholder']);
            @endphp
            {{-- Pada multiple, garis aktif menandai kotak yang diisi klik berikutnya. --}}
            <button
                type="button"
                id="{{ $boxId }}"
                @disabled($disabled)
                popovertarget="{{ $panelId }}"
                popovertargetaction="{{ $double ? 'show' : 'toggle' }}"
                aria-haspopup="dialog"
                aria-expanded="false"
                x-bind:aria-expanded="ceplokOpen ? 'true' : 'false'"
                aria-controls="{{ $panelId }}"
                aria-labelledby="{{ implode(' ', array_filter([$labelId, $double ? $boxId.'-nama' : null, $boxId.'-teks'])) }}"
                x-bind:data-aktif="ceplokActive('{{ $which }}') ? 'true' : null"
                x-on:click="ceplokPrepare($event)"
                @class([
                    'relative flex h-10.5 w-full items-center rounded-lg border pr-4 pl-9.5 text-left text-sm outline-none',
                    'transition-colors focus:border-primary-500 data-aktif:border-primary-500',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    $t['box'],
                ])
            >
                {{-- Ketiga span dirapatkan: spasi di antaranya ikut terbaca di textContent tombolnya. --}}
                <span @class(['pointer-events-none absolute inset-y-0 left-4 flex items-center', $t['icon']])><svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" /></svg></span>@if ($boxName !== null)<span id="{{ $boxId }}-nama" hidden>{{ $boxName }}</span>@endif<span id="{{ $boxId }}-teks" class="{{ $words->classes() }}" x-bind:class="{{ $words->binding() }}" x-text="ceplokText('{{ $which }}') ?? @js($empty)">{{ $initial ?? $empty }}</span>
            </button>
        @endforeach
    </div>

    @if (filled($name))
        @if ($type === 'single')
            <input type="hidden" name="{{ $name }}" value="{{ $start ?? '' }}" x-bind:value="ceplokIso('start')" />
        @else
            <input type="hidden" name="{{ $name }}[start]" value="{{ $start ?? '' }}" x-bind:value="ceplokIso('start')" />
            <input type="hidden" name="{{ $name }}[end]" value="{{ $end ?? '' }}" x-bind:value="ceplokIso('end')" />
        @endif
    @endif

    <div
        x-ref="panel"
        id="{{ $panelId }}"
        popover="auto"
        role="dialog"
        aria-labelledby="{{ $labelId }}"
        @class([
            'fixed inset-auto m-0 max-h-[calc(100dvh-1rem)] max-w-[calc(100vw-1rem)] overflow-y-auto rounded-lg p-4',
            'shadow-[0_4px_6px_rgb(0_0_0/0.05),0_10px_15px_-3px_rgb(0_0_0/0.1)]',
            $panelWidth,
            $t['panel'],
        ])
    >
        @if ($double)
            {{-- Berdampingan mulai layar `sm`. Pada mobile selalu bertumpuk, berjarak 24px. --}}
            <div @class(['grid', $stacked ? 'gap-6' : 'gap-8 sm:grid-cols-2'])>
        @endif

        @foreach ($sides as $side => $sideId)
            {{-- Satu kalender bulanan: judul dengan panah, nama hari, lalu kisi tanggal. --}}
            <div>
                <div class="flex h-4.5 items-center justify-between">
                    <button type="button" x-on:click="ceplokShift('{{ $side }}', -1)" x-bind:disabled="! ceplokCanPrev('{{ $side }}')" aria-label="Bulan sebelumnya" class="{{ $arrow }}">
                        <svg class="h-3 w-4" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14.8 6H1.2m0 0 4.5 4.8M1.2 6l4.5-4.8" />
                        </svg>
                    </button>
                    <p id="{{ $base }}-judul-{{ $sideId }}" aria-live="polite" @class(['text-xs font-bold', $t['title']]) x-text="ceplokTitle('{{ $side }}')"></p>
                    <button type="button" x-on:click="ceplokShift('{{ $side }}', 1)" x-bind:disabled="! ceplokCanNext('{{ $side }}')" aria-label="Bulan berikutnya" class="{{ $arrow }}">
                        <svg class="h-3 w-4 rotate-180" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14.8 6H1.2m0 0 4.5 4.8M1.2 6l4.5-4.8" />
                        </svg>
                    </button>
                </div>

                <div
                    role="grid"
                    aria-labelledby="{{ $base }}-judul-{{ $sideId }}"
                    x-on:keydown="ceplokGridKeydown($event)"
                    x-on:keyup="ceplokGridKeyup($event)"
                    @class(['mt-2 grid grid-cols-7', $grid])
                >
                    <div role="row" class="contents">
                        @foreach ($weekdays as $weekday)
                            <div role="columnheader" aria-label="{{ $weekday }}" @class(['flex h-8.5 items-center justify-center text-xs font-medium', $t['weekday']])>{{ mb_substr($weekday, 0, 3) }}</div>
                        @endforeach
                    </div>

                    <template x-for="week in ceplokWeeks('{{ $side }}')" x-bind:key="week.key">
                        <div role="row" class="contents">
                            <template x-for="day in week.days" x-bind:key="day.key">
                                <div role="gridcell" x-bind:aria-selected="day.selected ? 'true' : 'false'" class="h-8.5">
                                    {{-- Hanya tanggal di dalam bulannya yang bisa menerima Tab: tanggal luar bulan juga muncul di kalender sebelah. --}}
                                    <button
                                        type="button"
                                        x-bind:tabindex="day.focus ? 0 : -1"
                                        x-bind:data-fokus="day.focus ? 'true' : null"
                                        x-bind:disabled="! day.enabled"
                                        x-bind:aria-label="day.label"
                                        x-bind:aria-current="day.current ? 'date' : null"
                                        x-on:click="ceplokPick(day.date)"
                                        class="flex size-full items-center justify-center text-xs font-semibold transition-colors disabled:cursor-not-allowed"
                                        x-bind:class="day.class"
                                        x-text="day.n"
                                    ></button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        @endforeach

        @if ($double)
            </div>
        @endif

        {{-- Pada multiple semua jaraknya 16px, kecuali antarbaris pintasan di mobile yang 12px; pada satu kalender 8px. --}}
        @php
            $variant = $darkMode ? 'filled' : 'outline';
            $clear = 'inline-flex h-8.5 w-full items-center justify-center rounded-lg border text-xs font-medium transition-colors duration-200 focus:ring-2 focus:ring-primary-400 focus:outline-none '
                .($tight ? 'whitespace-nowrap' : 'px-4').' '.($double ? $t['clearDouble'] : $t['clear']);
        @endphp
        @if ($periodShortcuts)
            <div @class(['grid', $shortcutGrid, $double ? 'mt-4' : 'mt-2'])>
                @foreach (['hari' => 'Hari ini', 'minggu' => 'Minggu ini', 'bulan' => 'Bulan ini'] as $shortcut => $title)
                    <x-ceplok::button size="xs" :variant="$variant" :class="'w-full'.($tight ? ' whitespace-nowrap px-0!' : '')" x-bind:disabled="ceplokShortcutDisabled('{{ $shortcut }}')" x-on:click="ceplokApply('{{ $shortcut }}')">{{ $title }}</x-ceplok::button>
                @endforeach
                {{-- Tidak ada varian Button berlatar netral, jadi Hapus dibentuk sendiri dengan ukuran dan cincin fokus Button `xs`. --}}
                <button type="button" x-on:click="ceplokApply('hapus')" class="{{ $clear }}">Hapus</button>
            </div>
            <div @class([$double ? 'mt-4' : 'mt-2'])>
                <x-ceplok::button size="xs" :variant="$variant" class="w-full" x-bind:disabled="ceplokShortcutDisabled('semua')" x-on:click="ceplokApply('semua')">Semua Waktu</x-ceplok::button>
            </div>
        @else
            <div @class(['grid grid-cols-2 gap-4', $double ? 'mt-4' : 'mt-2'])>
                <x-ceplok::button size="xs" :variant="$variant" class="w-full" x-bind:disabled="ceplokShortcutDisabled('hari')" x-on:click="ceplokApply('hari')">Hari ini</x-ceplok::button>
                <button type="button" x-on:click="ceplokApply('hapus')" class="{{ $clear }}">Hapus</button>
            </div>
        @endif
    </div>
</div>
