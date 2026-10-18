@php
    use CeplokUi\DesignKit\Enums\BreadcrumbBackground;
    use CeplokUi\DesignKit\Enums\BreadcrumbSize;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Breadcrumb — jejak lokasi halaman.

    `items` berisi array: `label` (wajib), `href`, `icon` (markup, mis. hasil
    svg(), atau nama ikon seperti `ceplok-o-user`), dan `attributes` untuk
    atribut tambahan pada tautannya — padanan onClick di React, mis.
    `['wire:navigate' => true]`. Item bernama "Home" otomatis berikon rumah.
    Item terakhir adalah halaman aktif (`aria-current="page"`) dan tidak
    pernah menjadi tautan.

    Pemisah bawaannya chevron; ganti lewat slot `separator`.
--}}
@props([
    'items' => [],
    'size' => 'base',
    'background' => 'none',
    'separator' => null,
    'darkMode' => false,
])

@php
    $size = Prop::oneOf(BreadcrumbSize::class, $size, BreadcrumbSize::Base, 'breadcrumb', 'size');
    $background = Prop::oneOf(BreadcrumbBackground::class, $background, BreadcrumbBackground::None, 'breadcrumb', 'background');
    $darkMode = Prop::bool($darkMode, false, 'breadcrumb', 'dark-mode');
    $text = ['sm' => 'text-xs', 'base' => 'text-sm'][$size];
    $items = array_values(is_iterable($items) ? collect($items)->all() : []);
    $separator = Content::filled($separator) ? $separator : svg('ceplok-o-chevron-right');
@endphp

<nav
    {{ $attributes
        ->class(['flex items-center', 'rounded-lg bg-breadcrumb-surface px-4 py-3' => $background === 'bg'])
        ->merge(['aria-label' => 'Breadcrumb'] + ($darkMode ? ['data-theme' => 'dark'] : [])) }}
>
    <ol class="flex items-center gap-0">
        @foreach ($items as $index => $item)
            @php
                $isLast = $index === count($items) - 1;
                $label = (string) ($item['label'] ?? '');
                $icon = $item['icon'] ?? (strtolower($label) === 'home' ? svg('ceplok-s-home', 'size-4') : null);
            @endphp
            <li class="flex items-center gap-0">
                @if (! $isLast && filled($item['href'] ?? null))
                    <a
                        {{ (new ComponentAttributeBag($item['attributes'] ?? []))
                            ->class(['flex items-center gap-2 transition-colors', $text, 'text-content-muted hover:text-breadcrumb-link-hover'])
                            ->merge(['href' => $item['href']]) }}
                    >
                        @if (Content::filled($icon))
                            <span class="flex shrink-0 items-center justify-center">{!! Content::icon($icon) !!}</span>
                        @endif
                        {{ $label }}
                    </a>
                @else
                    <span
                        @if ($isLast) aria-current="page" @endif
                        @class([$text, $isLast ? 'font-medium text-breadcrumb-active' : 'text-content-muted'])
                    >
                        @if (Content::filled($icon))
                            <span class="flex shrink-0 items-center justify-center">{!! Content::icon($icon) !!}</span>
                        @endif
                        {{ $label }}
                    </span>
                @endif

                @if (! $isLast)
                    <span aria-hidden="true" class="{{ $text }} text-gray-400">{{ $separator }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
