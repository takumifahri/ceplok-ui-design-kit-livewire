@php
    use CeplokUi\DesignKit\Enums\ModalSize;
    use CeplokUi\DesignKit\Enums\ModalVariant;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
    use Illuminate\View\ComponentAttributeBag;
@endphp

{{--
    Modal — dialog di atas halaman berbasis `<dialog>` bawaan, port Modal di
    kit React. Peramban yang mengurus top layer, latar yang ikut mati, dan
    jebakan fokus.

    `variant="default"`: `title` (prop atau slot) mengisi header sekaligus
    menjadi nama aksesibel, slot bawaan mengisi badan, dan slot `footer`
    mengisi kakinya. `variant="popup"` untuk konfirmasi ringkas: tanpa judul
    dan tanpa divider footer; beri `aria-label` sebagai nama aksesibelnya.
    `size` (`s` | `m`) hanya untuk default.

    Pembukanya slot `trigger`, dirender sebagai Button kit dengan atribut slot
    sebagai props-nya, atau `<button>` polos dengan `raw` — sama seperti
    Dropdown. `wire:model` atau `x-model` di tag Modal mengendalikan buka-tutup
    (padanan `open` + `onOpenChange`), dan setiap perubahan mengirim event
    `open-change` (`detail.open`). Dengan pengikatan itu trigger boleh tidak
    ada, mis. Modal yang dibuka dari aksi baris Table. Di dalam Modal,
    `x-on:click="close()"` menutupnya, padanan render function `footer({ close })`.

    Beda dari React, karena Livewire:
    - `<dialog>` dipasang `wire:ignore.self`. Morph Livewire menutup dialog yang
      sedang terbuka setiap kali komponennya dirender ulang (atribut `open`
      tidak ada di HTML server). Isi dialog tetap ikut dirender ulang; hanya
      atribut dialognya sendiri yang dikunci, jadi ganti `wire:key` bila
      `variant` atau `size` perlu berubah.
    - `close-on-escape` dan `close-on-backdrop` dibawa atribut `data-*` dialog
      supaya ekspresi x-data tetap sama di setiap render.

    `wire:model`, `x-model`, dan `wire:key` dipasang di pembungkus (`contents`);
    atribut lain diteruskan ke `<dialog>`, seperti di React.
--}}
@props([
    'variant' => 'default',
    'size' => null,
    'title' => null,
    'closeLabel' => 'Tutup modal',
    'closeOnBackdrop' => true,
    'closeOnEscape' => true,
    'showCloseButton' => true,
    'open' => false,
    'trigger' => null,
    'footer' => null,
])

@php
    $variant = Prop::oneOf(ModalVariant::class, $variant, ModalVariant::Default, 'modal', 'variant');
    $isPopup = $variant === 'popup';
    $closeOnBackdrop = Prop::bool($closeOnBackdrop, true, 'modal', 'close-on-backdrop');
    $closeOnEscape = Prop::bool($closeOnEscape, true, 'modal', 'close-on-escape');
    $showCloseButton = Prop::bool($showCloseButton, true, 'modal', 'show-close-button');

    if ($isPopup && ($size !== null || Content::filled($title))) {
        Prop::violation('<x-ceplok::modal variant="popup"> tidak memakai `size` dan `title`. Beri nama aksesibelnya lewat `aria-label`, lalu susun ikon dan pesannya di slot bawaan.');
    }

    $size = $isPopup ? null : Prop::oneOf(ModalSize::class, $size ?? 's', ModalSize::S, 'modal', 'size');
    $bound = Bound::has($attributes);
    $open = Bound::bool($attributes, Prop::bool($open, false, 'modal', 'open'));
    $hasTrigger = $trigger !== null;

    if (! $hasTrigger && ! $bound) {
        Prop::violation('<x-ceplok::modal> memerlukan slot `trigger`, atau `wire:model`/`x-model` untuk membukanya dari luar.');
    }

    $titleId = FieldId::for($attributes, 'modal').'-title';
    $hasTitle = ! $isPopup && Content::filled($title);
    $ariaLabel = filled(trim((string) $attributes->get('aria-label'))) ? $attributes->get('aria-label') : null;
    $ariaLabelledBy = filled(trim((string) $attributes->get('aria-labelledby'))) ? $attributes->get('aria-labelledby') : null;
    $divider = 'border-[light-dark(var(--color-border),var(--color-border-strong))]';

    $wrapperKeys = array_keys($attributes->whereStartsWith(['wire:model', 'x-model', 'wire:key'])->getAttributes());
    $wrapper = $attributes->only($wrapperKeys);
    $dialog = $attributes
        ->except([...$wrapperKeys, 'aria-label', 'aria-labelledby', 'open'])
        ->merge([
            'aria-label' => $ariaLabel,
            'aria-labelledby' => $ariaLabelledBy ?? ($hasTitle && $ariaLabel === null ? $titleId : null),
            'data-close-on-escape' => $closeOnEscape ? 'true' : 'false',
            'data-close-on-backdrop' => $closeOnBackdrop ? 'true' : 'false',
        ])
        ->class([
            'm-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-hidden rounded-lg border p-0 text-left text-content shadow-xl',
            $divider,
            'backdrop:bg-gray-900/50 open:flex open:flex-col',
            $isPopup ? 'max-w-[416px] bg-[light-dark(var(--color-surface),var(--color-gray-600))]' : null,
            $size === 's' ? 'max-w-[416px] bg-surface' : null,
            $size === 'm' ? 'max-w-[640px] bg-surface' : null,
        ]);

    if ($hasTrigger) {
        $triggerAttributes = ($trigger->attributes ?? new ComponentAttributeBag)->merge([
            'aria-haspopup' => 'dialog',
            'aria-expanded' => $open ? 'true' : 'false',
            'x-bind:aria-expanded' => "open ? 'true' : 'false'",
            'x-on:click' => 'show()',
        ]);
        $raw = $triggerAttributes->has('raw');
    }
@endphp

<div x-data="ceplokModal" x-modelable="open" data-open="{{ $open ? 'true' : 'false' }}" {{ $wrapper->class('contents') }}>
    @if ($hasTrigger)
        @if ($raw)
            <button {{ $triggerAttributes->except('raw')->merge(['type' => 'button']) }}>{{ $trigger }}</button>
        @else
            <x-ceplok::button :attributes="$triggerAttributes">{{ $trigger }}</x-ceplok::button>
        @endif
    @endif

    <dialog
        x-ref="dialog"
        wire:ignore.self
        x-on:cancel="onCancel($event)"
        x-on:close="onNativeClose()"
        x-on:click="onClick($event)"
        {{ $dialog }}
    >
        <div @class([
            'flex shrink-0 items-start justify-between gap-4',
            'relative h-7 px-5' => $isPopup,
            'px-6' => ! $isPopup,
            'border-b '.$divider.' py-5' => ! $isPopup && $hasTitle,
            'pt-4' => ! $isPopup && ! $hasTitle,
        ])>
            @if ($hasTitle)
                <h2 id="{{ $titleId }}" class="min-w-0 flex-1 font-alt text-lg leading-[1.5] font-semibold text-content">{{ $title }}</h2>
            @endif

            @if ($showCloseButton)
                <button
                    type="button"
                    x-on:click="close()"
                    aria-label="{{ $closeLabel }}"
                    @class([
                        'ml-auto inline-flex shrink-0 items-center justify-center rounded-lg text-[light-dark(var(--color-gray-400),var(--color-content-muted))] transition-colors hover:bg-[light-dark(var(--color-gray-100),var(--color-surface-subtle))] hover:text-[light-dark(var(--color-gray-600),var(--color-content))] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand',
                        $isPopup ? 'absolute right-0.5 top-0.5 size-10' : '-my-2 -mr-2 size-10',
                    ])
                >
                    {{ svg('ceplok-o-close', 'size-4', ['aria-hidden' => 'true']) }}
                </button>
            @endif
        </div>

        <div @class([
            'min-h-0 flex-1 overflow-y-auto overscroll-y-contain font-sans text-body font-medium text-content-muted',
            $isPopup ? 'px-5 pb-4 pt-5' : 'px-5 py-6',
        ])>{{ $slot }}</div>

        @if (Content::filled($footer))
            <div @class([
                'flex shrink-0 flex-wrap items-center gap-3',
                $isPopup ? 'px-5 pb-5' : 'border-t '.$divider.' px-6 py-4',
            ])>{{ $footer }}</div>
        @endif
    </dialog>
</div>
