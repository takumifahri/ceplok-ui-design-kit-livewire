@php
    use CeplokUi\DesignKit\Enums\PopoverSide;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\ClassToggle;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Popover — panel informasi non-modal yang ditambatkan pada satu trigger,
    port Popover di kit React. Peramban yang mengurus top layer, Escape, dan
    light dismiss lewat Popover API.

    `title` (prop atau slot) mengisi header, slot bawaan mengisi badan.
    Triggernya slot `trigger`: Button kit dengan atribut slot sebagai props,
    atau `<button>` polos dengan `raw` — sama seperti Dropdown. Popover
    memasang `id`, `aria-controls`, dan `aria-expanded` di trigger; trigger
    tetap butuh nama aksesibel sendiri, mis. `aria-label` pada tombol ikon.

    `side` adalah preferensi posisi; panel berbalik ke sisi berlawanan bila
    ruangnya kurang, dan dijepit di dalam layar. `dark-mode` memakai panel
    gelap. `open` membuka sejak awal (padanan `defaultOpen`); `wire:model` atau
    `x-model` di tag Popover mengendalikannya (padanan `open` + `onOpenChange`),
    dan setiap perubahan mengirim event `open-change` (`detail.open`).

    `wire:model`, `x-model`, dan `wire:key` dipasang di pembungkus (`contents`);
    atribut lain diteruskan ke panel, seperti di React.
--}}
@props([
    'title',
    'side' => 'right',
    'darkMode' => false,
    'open' => false,
    'trigger',
])

@php
    $side = Prop::oneOf(PopoverSide::class, $side, PopoverSide::Right, 'popover', 'side');
    $darkMode = Prop::bool($darkMode, false, 'popover', 'dark-mode');
    $open = Bound::bool($attributes, Prop::bool($open, false, 'popover', 'open'));

    $panelId = filled($attributes->get('id')) ? $attributes->get('id') : FieldId::for($attributes, 'popover');
    $triggerSlot = $trigger->attributes ?? new ComponentAttributeBag;
    $triggerId = filled(trim((string) $triggerSlot->get('id'))) ? trim($triggerSlot->get('id')) : $panelId.'-trigger';
    $controls = collect(preg_split('/\s+/', (string) $triggerSlot->get('aria-controls'), flags: PREG_SPLIT_NO_EMPTY))
        ->push($panelId)
        ->unique()
        ->implode(' ');

    $triggerAttributes = $triggerSlot->except(['id', 'aria-controls'])->merge([
        'id' => $triggerId,
        'aria-controls' => $controls,
        'aria-expanded' => $open ? 'true' : 'false',
        'x-bind:aria-expanded' => "open ? 'true' : 'false'",
        'x-ref' => 'trigger',
        'x-on:click' => 'toggle($event)',
    ]);
    $raw = $triggerAttributes->has('raw');

    $wrapperKeys = array_keys($attributes->whereStartsWith(['wire:model', 'x-model', 'wire:key'])->getAttributes());
    $wrapper = $attributes->only($wrapperKeys);

    // Sebelum posisinya terukur panel disembunyikan, supaya tidak terlihat melompat.
    $panelClass = (new ClassToggle)
        ->always('fixed inset-auto m-0 w-[255px] shrink-0 overflow-visible border-0 bg-transparent p-0 [filter:drop-shadow(0_1px_2px_rgb(0_0_0/0.08))]')
        ->when('ready', false, 'visible', 'invisible');
    $panel = $attributes
        ->except([...$wrapperKeys, 'id', 'popover'])
        ->merge([
            'id' => $panelId,
            'data-side' => $side,
            'x-ref' => 'panel',
            'x-bind:style' => 'panelStyle()',
            'x-bind:class' => $panelClass->binding(),
        ])
        ->class($panelClass->classes());

    // Panah berada pada tepi panel yang menghadap trigger. Sisi akhirnya bisa
    // berbalik, jadi keempat pilihannya diikat ke state Alpine.
    $arrowSides = [
        'top' => 'bottom-[-8px] rotate-180',
        'right' => 'left-[-8px] -rotate-90',
        'bottom' => 'top-[-8px]',
        'left' => 'right-[-8px] rotate-90',
    ];
    $arrow = (new ClassToggle)->always('pointer-events-none absolute z-30 size-4');
    foreach ($arrowSides as $name => $classes) {
        $arrow->when("side === '{$name}'", $side === $name, $classes);
    }

    // Warna panah mengikuti surface yang disentuhnya: header atau badan.
    $surfaces = $darkMode
        ? ['header' => 'bg-gray-700', 'body' => 'bg-gray-800']
        : ['header' => 'bg-gray-50', 'body' => 'bg-surface'];
    $arrowSurface = (new ClassToggle)
        ->always('absolute top-[3.25px] left-[3px] size-2.5 rotate-45 border-t border-l '.($darkMode ? 'border-gray-800' : 'border-border'))
        ->when("arrowSurface === 'header'", $side === 'bottom', $surfaces['header'], $surfaces['body']);
@endphp

<div x-data="ceplokPopover" x-modelable="open" data-open="{{ $open ? 'true' : 'false' }}" {{ $wrapper->class('contents') }}>
    @if ($raw)
        <button {{ $triggerAttributes->except('raw')->merge(['type' => 'button']) }}>{{ $trigger }}</button>
    @else
        <x-ceplok::button :attributes="$triggerAttributes">{{ $trigger }}</x-ceplok::button>
    @endif

    <div popover="auto" {{ $panel }}>
        <span aria-hidden="true" x-bind:style="arrowStyle()" class="{{ $arrow->classes() }}" x-bind:class="{{ $arrow->binding() }}">
            <span class="{{ $arrowSurface->classes() }}" x-bind:class="{{ $arrowSurface->binding() }}"></span>
        </span>

        <div @class(['relative z-10 overflow-hidden rounded-md', $darkMode ? 'bg-gray-800' : 'bg-surface'])>
            <div @class([
                'border-b px-3 py-1.5 text-sm leading-[1.5] font-semibold [overflow-wrap:anywhere]',
                $darkMode ? 'border-gray-800 bg-gray-700 text-gray-50' : 'border-border bg-gray-50 text-gray-900',
            ])>{{ $title }}</div>
            <div @class([
                'px-3 py-2 text-sm leading-[1.5] font-medium [overflow-wrap:anywhere]',
                $darkMode ? 'bg-gray-800 text-gray-400' : 'bg-surface text-gray-500',
            ])>{{ $slot }}</div>
            <span aria-hidden="true" @class(['pointer-events-none absolute inset-0 z-20 rounded-md border', $darkMode ? 'border-gray-800' : 'border-border'])></span>
        </div>
    </div>
</div>
