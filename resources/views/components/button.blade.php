@php
    use CeplokUi\DesignKit\Enums\ButtonAs;
    use CeplokUi\DesignKit\Enums\ButtonSize;
    use CeplokUi\DesignKit\Enums\ButtonTheme;
    use CeplokUi\DesignKit\Enums\ButtonTone;
    use CeplokUi\DesignKit\Enums\ButtonType;
    use CeplokUi\DesignKit\Enums\ButtonVariant;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Button — tombol atau tautan berbentuk tombol.

    Warnanya diatur `theme` (primary, green, gray, purple, orange, yellow) dan
    `tone` (light, dark); `variant` hanya memilih terisi atau bergaris. Ikon di
    kiri/kanan teks lewat slot `left-icon` / `right-icon`; `icon-only` membuat
    tombol bundar berisi ikon saja — beri `aria-label`.

    Dirender sebagai <a> bila `as="anchor"`, atau bila `href` diisi tanpa `as`.
    `type` (bawaan "button") hanya dipasang pada <button>.
--}}
@props([
    'variant' => 'filled',
    'size' => 'base',
    'theme' => 'primary',
    'tone' => 'light',
    'iconOnly' => false,
    'as' => null,
    'type' => 'button',
    'leftIcon' => null,
    'rightIcon' => null,
])

@php
    $variant = Prop::oneOf(ButtonVariant::class, $variant, ButtonVariant::Filled, 'button', 'variant');
    $size = Prop::oneOf(ButtonSize::class, $size, ButtonSize::Base, 'button', 'size');
    $theme = Prop::oneOf(ButtonTheme::class, $theme, ButtonTheme::Primary, 'button', 'theme');
    $tone = Prop::oneOf(ButtonTone::class, $tone, ButtonTone::Light, 'button', 'tone');
    $iconOnly = Prop::bool($iconOnly, false, 'button', 'icon-only');
    $anchor = $as === null
        ? $attributes->has('href')
        : Prop::oneOf(ButtonAs::class, $as, ButtonAs::Button, 'button', 'as') === 'anchor';

    $sizes = [
        'xs' => ['button' => 'h-[34px] px-4 gap-2 rounded-lg text-xs', 'icon' => 'size-3', 'iconOnly' => 'h-[34px] w-[34px] rounded-full p-0'],
        's' => ['button' => 'h-[38px] px-4 gap-2 rounded-lg text-sm', 'icon' => 'size-3', 'iconOnly' => 'h-[38px] w-[38px] rounded-full p-0'],
        'base' => ['button' => 'h-[40px] px-4 gap-2 rounded-lg text-base', 'icon' => 'size-3.5', 'iconOnly' => 'h-[40px] w-[40px] rounded-full p-0'],
        'l' => ['button' => 'h-[43px] px-5 gap-2 rounded-lg text-lg', 'icon' => 'size-3.5', 'iconOnly' => 'h-[43px] w-[43px] rounded-full p-0'],
        'xl' => ['button' => 'h-[46px] px-6 gap-2 rounded-lg text-xl', 'icon' => 'size-4', 'iconOnly' => 'h-[46px] w-[46px] rounded-full p-0'],
    ];

    $colors = [
        'primary' => [
            'light' => [
                'filled' => 'bg-primary-700 text-white hover:bg-primary-800',
                'outline' => 'border border-primary-700 text-primary-700 hover:bg-primary-50',
            ],
            'dark' => [
                'filled' => 'bg-primary-800 text-white hover:bg-primary-700',
                'outline' => 'border border-primary-800 text-primary-800 hover:bg-primary-50',
            ],
        ],
        'green' => [
            'light' => [
                'filled' => 'bg-green-700 text-white hover:bg-green-800',
                'outline' => 'border border-green-700 text-green-700 hover:bg-green-50',
            ],
            'dark' => [
                'filled' => 'bg-green-800 text-white hover:bg-green-700',
                'outline' => 'border border-green-800 text-green-800 hover:bg-green-50',
            ],
        ],
        'gray' => [
            // Gray light memakai 500, gray dark memakai 700.
            'light' => [
                'filled' => 'bg-gray-500 text-white hover:bg-gray-700',
                'outline' => 'border border-gray-500 text-gray-500 hover:bg-gray-100 hover:text-gray-700 hover:border-gray-700',
            ],
            'dark' => [
                'filled' => 'bg-gray-700 text-white hover:bg-gray-500',
                'outline' => 'border border-gray-700 text-gray-700 hover:bg-gray-100 hover:text-gray-900 hover:border-gray-900',
            ],
        ],
        'purple' => [
            'light' => [
                'filled' => 'bg-purple-700 text-white hover:bg-purple-800',
                'outline' => 'border border-purple-700 text-purple-700 hover:bg-purple-50',
            ],
            'dark' => [
                'filled' => 'bg-purple-800 text-white hover:bg-purple-700',
                'outline' => 'border border-purple-800 text-purple-800 hover:bg-purple-50',
            ],
        ],
        'orange' => [
            // Orange light memakai 600, orange dark memakai 700.
            'light' => [
                'filled' => 'bg-orange-600 text-white hover:bg-orange-700',
                'outline' => 'border border-orange-600 text-orange-600 hover:bg-orange-50',
            ],
            'dark' => [
                'filled' => 'bg-orange-700 text-white hover:bg-orange-700',
                'outline' => 'border border-orange-700 text-orange-700 hover:bg-orange-50',
            ],
        ],
        'yellow' => [
            'light' => [
                'filled' => 'bg-yellow-700 text-white hover:bg-yellow-800',
                'outline' => 'border border-yellow-700 text-yellow-700 hover:bg-yellow-50',
            ],
            'dark' => [
                'filled' => 'bg-yellow-800 text-white hover:bg-yellow-700',
                'outline' => 'border border-yellow-800 text-yellow-800 hover:bg-yellow-50',
            ],
        ],
    ];

    $s = $sizes[$size];
    $tag = $anchor ? 'a' : 'button';
    $classes = 'inline-flex items-center justify-center font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-400 disabled:pointer-events-none disabled:opacity-50 '
        .$colors[$theme][$tone][$variant].' '
        .($iconOnly ? $s['iconOnly'] : $s['button']);
    // Content::filled, bukan (string): svg() mengembalikan objek Htmlable yang
    // tidak bisa di-cast ke string.
    $hasLeftIcon = ! $iconOnly && Content::filled($leftIcon);
    $hasRightIcon = ! $iconOnly && Content::filled($rightIcon);
@endphp

<{{ $tag }} {{ $attributes->class($classes)->merge($anchor ? [] : ['type' => Prop::oneOf(ButtonType::class, $type, ButtonType::Button, 'button', 'type')]) }}>
    @if ($hasLeftIcon)
        <span class="flex shrink-0 items-center justify-center {{ $s['icon'] }}">{{ $leftIcon }}</span>
    @endif
    @if ($iconOnly)
        <span class="flex items-center justify-center {{ $s['icon'] }}">{{ $slot }}</span>
    @else
        {{ $slot }}
    @endif
    @if ($hasRightIcon)
        <span class="flex shrink-0 items-center justify-center {{ $s['icon'] }}">{{ $rightIcon }}</span>
    @endif
</{{ $tag }}>
