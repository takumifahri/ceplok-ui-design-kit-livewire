@php
    use CeplokUi\DesignKit\Enums\SelectApplication;
    use CeplokUi\DesignKit\Enums\SelectState;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\ClassToggle;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Options;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\Support\Js;
@endphp

{{--
    Regular Select Form — dropdown satu pilihan.

    Daftar pilihannya panel kit, bukan popup milik sistem operasi, jadi rupanya
    seragam dengan menu lain. Nilainya tetap dibawa `<select>` yang dirender
    tersembunyi di belakang tombolnya: `name`, `wire:model`, `x-model`,
    `required`, dan pengiriman formulir bekerja seperti pada `<select>` biasa,
    dan atribut lain diteruskan ke sana. `class` dan `wire:key` ke pembungkus
    terluar.

    Pilihan lewat prop `options` (daftar `['value' => …, 'label' => …,
    'disabled' => …]`, pasangan `nilai => label`, atau kelompok
    `['label' => …, 'options' => […]]`), atau lewat `<option>` dan `<optgroup>`
    di slot. `value` adalah pilihan awalnya; di dalam Livewire pilihan awal
    `wire:model` dibaca dari propertinya. `placeholder` menjadi baris pertama
    bernilai kosong.

    `info` memasang ikon info berketerangan di samping label. `state="inactive"`
    atau `disabled` menonaktifkan kontrol. `dark-mode` memasang
    `data-theme="dark"`; panel pilihannya tetap terang, sesuai desain.
--}}
@props([
    'label' => null,
    'info' => null,
    'helperText' => null,
    'placeholder' => null,
    'options' => null,
    'application' => 'default',
    'state' => 'default',
    'darkMode' => false,
])

@php
    $application = Prop::oneOf(SelectApplication::class, $application, SelectApplication::Default, 'select', 'application');
    $state = Prop::oneOf(SelectState::class, $state, SelectState::Default, 'select', 'state');
    $darkMode = Prop::bool($darkMode, false, 'select', 'dark-mode');

    $id = FieldId::for($attributes, 'select');
    $labelId = $id.'-label';
    $helperId = $id.'-helper';
    $listboxId = $id.'-listbox';
    $inactive = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true) || $state === 'inactive';
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);
    $hasPlaceholder = filled($placeholder);
    $ariaLabel = $attributes->get('aria-label');

    // Placeholder menjadi baris pilihan biasa, seperti <option value=""> pertama di <select>.
    $fromSlot = $options === null;
    $slotHtml = $fromSlot ? trim($slot->toHtml()) : '';
    $groups = $fromSlot ? Options::fromHtml($slotHtml) : Options::fromArray($options);
    $start = $hasPlaceholder ? [['value' => '', 'label' => (string) $placeholder, 'disabled' => false, 'selected' => false]] : [];

    if ($start !== []) {
        if ($groups !== [] && $groups[0]['label'] === null) {
            array_unshift($groups[0]['options'], ...$start);
        } else {
            array_unshift($groups, ['label' => null, 'options' => $start]);
        }
    }

    // Pilihan awal: `value`, properti wire:model, opsi ber-`selected`, lalu
    // yang dipilih peramban sendiri — kosong bila ada placeholder, opsi
    // pertama bila tidak. Hanya pilihan awal yang eksplisit yang ditulis
    // sebagai atribut `selected`, seperti defaultValue di React; pilihan
    // bawaan peramban dibiarkan ke perambannya.
    $explicit = $attributes->get('value') ?? Bound::text($attributes) ?? Options::selected($groups);
    $current = (string) ($explicit ?? ($hasPlaceholder ? '' : (Options::first($groups) ?? '')));
    $selectedAttr = $explicit !== null;
    // Pilihan awal untuk Alpine hanya bila markup belum membawanya. Yang diikat
    // wire:model/x-model dibiarkan ke ikatannya, supaya x-data tidak berubah
    // setiap kali Livewire merender ulang dengan nilai baru.
    $initial = Bound::has($attributes) || $selectedAttr ? null : $current;
    $choices = collect($groups)->flatMap(fn (array $group) => $group['options'])->map(fn (array $option) => [$option['value'], $option['label']])->values()->all();
    $currentLabel = collect($choices)->first(fn (array $choice) => $choice[0] === $current)[1] ?? ($placeholder ?? '');

    // Baris panel Dropdown: `selected` inilah yang membuatnya berperan sebagai
    // daftar pilihan; penempatan, papan ketik, dan penutupannya urusan Dropdown.
    $menuGroups = collect($groups)->map(fn (array $group, int $i) => [
        'id' => "kelompok-{$i}",
        'label' => $group['label'],
        'items' => array_map(fn (array $option) => [
            'label' => $option['label'],
            'disabled' => $option['disabled'],
            'selected' => $option['value'] === $current,
            'selected-when' => 'ceplokValue === '.Js::from($option['value']),
            'attributes' => ['x-on:click' => 'ceplokChoose('.Js::from($option['value']).')'],
        ], $group['options']),
    ])->all();

    $accents = [
        'default' => ['icon' => 'text-primary-500', 'focus' => 'focus-within:border-primary-500'],
        'purple' => ['icon' => 'text-purple-500', 'focus' => 'focus-within:border-purple-500'],
    ];

    // Warna dari token, jadi tema gelap cukup mengganti nilai token lewat
    // data-theme. Desain gelap: garis state default menyatu dengan latarnya;
    // justru `inactive` yang bergaris gray-300, di kedua tampilan.
    $inks = [
        'default' => [
            'label' => 'text-select-label',
            'icon' => null,
            'box' => 'border-field-border bg-field',
            'value' => 'text-content',
            'placeholder' => 'text-content-muted',
            'chevron' => 'text-content-muted',
            'helper' => 'text-content-muted',
        ],
        'inactive' => [
            'label' => 'text-content',
            'icon' => 'text-gray-400',
            'box' => 'border-gray-300 bg-field-disabled',
            'value' => 'text-select-content-inactive',
            'placeholder' => 'text-select-content-inactive',
            'chevron' => 'text-select-content-inactive',
            'helper' => 'text-content-disabled',
        ],
    ];
    $accent = $accents[$application];
    $ink = $inks[$inactive ? 'inactive' : 'default'];

    $triggerClasses = (new ClassToggle)
        ->always('flex h-9.25 w-full items-center pr-8 pl-2.5 text-left text-sm outline-none disabled:cursor-not-allowed')
        ->when("ceplokValue === ''", $current === '', $ink['placeholder'], $ink['value']);

    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $field = $attributes
        ->except(['class', 'wire:key', 'id', 'disabled', 'value'])
        ->merge(['aria-hidden' => 'true', 'tabindex' => '-1'])
        // Pembawa nilai saja: tombol di depannya yang terlihat, memegang id,
        // dan ditunjuk label. Ia disembunyikan lewat opacity, bukan `hidden`,
        // supaya peramban masih bisa memfokusnya untuk menampilkan pesan
        // validasi `required` di tempat yang benar.
        ->class('pointer-events-none absolute inset-0 size-full opacity-0');
@endphp

<div {{ $root->class('w-full') }} x-data="ceplokSelect(@js($initial), @js($placeholder ?? ''))">
    @if ($hasLabel)
        <div class="mb-2 flex items-center gap-2">
            <label for="{{ $id }}" id="{{ $labelId }}" @class(['text-sm font-bold', $ink['label']])>{{ $label }}</label>
            @if (filled($info))
                <span title="{{ $info }}" aria-label="{{ $info }}" @class(['flex shrink-0 items-center', $ink['icon'] ?? $accent['icon']])>
                    {{ svg('ceplok-s-info-circle', 'size-3') }}
                </span>
            @endif
        </div>
    @endif

    <div @class(['relative flex items-center rounded-lg border transition-colors', $ink['box'], $accent['focus'] => ! $inactive])>
        <select @disabled($inactive) {{ $field }}>
            @if ($hasPlaceholder)
                <option value="" @selected($selectedAttr && $current === '')>{{ $placeholder }}</option>
            @endif
            @if ($fromSlot)
                {!! $slotHtml === '' || ! $selectedAttr ? $slotHtml : Options::withSelected($slotHtml, $current) !!}
            @else
                @foreach ($groups as $group)
                    @if ($group['label'] !== null)
                        <optgroup label="{{ $group['label'] }}">
                    @endif
                    @foreach ($group['options'] as $option)
                        @continue($hasPlaceholder && $loop->parent->first && $loop->first)
                        {{--
                            wire:key: opsi yang diganti server — mis. kabupaten yang mengikuti
                            provinsi — dipasang sebagai elemen baru, bukan ditambal. Tanpa itu
                            morph Livewire mengganti nilai <option> yang sedang terpilih di
                            tempatnya, dan pilihan lama pindah ke opsi baru di posisi yang sama.
                        --}}
                        <option wire:key="{{ $option['value'] }}" value="{{ $option['value'] }}" @disabled($option['disabled']) @selected($selectedAttr && $option['value'] === $current)>{{ $option['label'] }}</option>
                    @endforeach
                    @if ($group['label'] !== null)
                        </optgroup>
                    @endif
                @endforeach
            @endif
        </select>

        {{-- Panel tinggal di dalam field ini (top layer tetap ikut pohon DOM), jadi tanpa ds-theme-light ia ikut gelap. Desainnya: panel tetap terang. --}}
        <x-ceplok::dropdown
            attached
            :panel-id="$listboxId"
            :content-label="is_string($label) ? $label : $ariaLabel"
            content-class="ds-theme-light"
            :groups="$menuGroups"
        >
            <x-slot:trigger
                raw
                :id="$id"
                :disabled="$inactive"
                :aria-labelledby="$hasLabel ? $labelId : null"
                :aria-label="$hasLabel ? null : $ariaLabel"
                :aria-describedby="$hasHelper ? $helperId : null"
                :class="$triggerClasses->classes()"
                :x-bind:class="$triggerClasses->binding()"
            >
                <span class="truncate" x-text="ceplokLabel">{{ $currentLabel }}</span>
            </x-slot:trigger>
        </x-ceplok::dropdown>

        <span aria-hidden="true" @class(['pointer-events-none absolute right-2.5 flex items-center', $ink['chevron']])>
            {{-- Panah 8×4 di dalam kotak ikon 12px, digambar sendiri supaya ukurannya persis seperti desain. --}}
            <svg class="size-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2 4.5 6 8.5l4-4" />
            </svg>
        </span>
    </div>

    @if ($hasHelper)
        <p id="{{ $helperId }}" @class(['mt-2 text-sm', $ink['helper']])>{{ $helperText }}</p>
    @endif
</div>
