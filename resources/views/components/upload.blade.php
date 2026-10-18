@php
    use CeplokUi\DesignKit\Enums\UploadApplication;
    use CeplokUi\DesignKit\Enums\UploadPlatform;
    use CeplokUi\DesignKit\Enums\UploadType;
    use CeplokUi\DesignKit\Support\ClassToggle;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Upload Form — pemilih satu berkas.

    Type `default` berupa satu baris: tombol pilih berkas di kiri dan nama
    berkas terpilih di kanan, lalu tombol hapus dan ukuran berkas begitu ada
    yang dipilih. Type `attach` berupa area seret-lepas bergaris putus-putus
    setinggi 230px yang melebar mengikuti wadahnya.

    Di dalamnya tetap `<input type="file">` sungguhan yang hanya disembunyikan
    secara visual, jadi dialog berkas, fokus keyboard, `wire:model` (unggahan
    Livewire), dan pengiriman formulir bekerja apa adanya; atribut lain
    (`name`, `accept`, `wire:model`, …) diteruskan ke sana. Setiap berkas
    berganti, termasuk lewat seret-lepas, input-nya memicu event `files-change`
    (`detail.files`), padanan onFilesChange di React. `multiple` tidak
    didukung: unggahannya selalu tunggal.

    Label tombol dan teks lain berupa string, karena ikut berganti di browser
    saat berkas dipilih.
--}}
@props([
    'label' => null,
    'helperText' => null,
    'buttonLabel' => 'Pilih File',
    'replaceLabel' => 'Replace file',
    'removeLabel' => 'Remove',
    'placeholder' => 'Belum ada file yang dipilih',
    'type' => 'default',
    'platform' => 'default',
    'application' => 'default',
    'attachLabel' => 'Click to upload or drag and drop',
    'attachHint' => 'SVG, PNG, JPG or GIF (MAX. 800x400px)',
    'darkMode' => false,
])

@php
    $type = Prop::oneOf(UploadType::class, $type, UploadType::Default, 'upload', 'type');
    $platform = Prop::oneOf(UploadPlatform::class, $platform, UploadPlatform::Default, 'upload', 'platform');
    $application = Prop::oneOf(UploadApplication::class, $application, UploadApplication::Default, 'upload', 'application');
    $darkMode = Prop::bool($darkMode, false, 'upload', 'dark-mode');

    $id = FieldId::for($attributes, 'upload');
    $helperId = $id.'-helper';
    $disabled = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true);
    $hasLabel = Content::filled($label);
    $hasHelper = Content::filled($helperText);
    $attach = $type === 'attach';

    // Tinggi kotak, ukuran teks, dan jarak samping tombol per platform. Jarak
    // sampingnya nilai lepas: tombolnya harus jadi selebar 94px dan 86px.
    $platforms = [
        'default' => ['field' => 'h-11', 'text' => 'text-sm', 'button' => 'px-[22px] text-sm'],
        'mobile' => ['field' => 'h-10', 'text' => 'text-xs', 'button' => 'px-[21px] text-xs'],
    ];

    // `solid` untuk tombol pilih berkas, `drag` untuk sorotan area attach
    // selama berkas ditahan di atasnya.
    $accents = [
        'default' => ['solid' => 'bg-primary-700 hover:bg-primary-800 focus-visible:outline-primary-700', 'drag' => 'border-primary-500 bg-primary-50', 'dragDark' => 'border-primary-500 bg-gray-600'],
        'purple' => ['solid' => 'bg-purple-500 hover:bg-purple-600 focus-visible:outline-purple-500', 'drag' => 'border-purple-500 bg-purple-50', 'dragDark' => 'border-purple-500 bg-gray-600'],
    ];
    $size = $platforms[$platform];
    $accent = $accents[$application];
    $muted = $disabled ? ($darkMode ? 'text-gray-600' : 'text-gray-400') : ($darkMode ? 'text-gray-400' : 'text-gray-500');

    // Area attach: sorotan saat seret ditambahkan Alpine.
    $zone = (new ClassToggle)->always('flex h-[230px] w-full flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 text-center transition-colors');
    if ($disabled) {
        $zone->always($darkMode ? 'cursor-not-allowed border-gray-700 bg-gray-800' : 'cursor-not-allowed border-gray-200 bg-gray-100');
    } else {
        $zone->always('cursor-pointer '.($darkMode ? 'border-gray-500 bg-gray-700 hover:bg-gray-600' : 'border-gray-200 bg-gray-50 hover:bg-gray-100'))
            ->when('ceplokDrag', false, $darkMode ? $accent['dragDark'] : $accent['drag']);
    }

    // Nama berkas: putih saat terisi di tampilan gelap.
    $name = (new ClassToggle)->always('flex min-w-0 flex-1 items-center px-4 transition-colors '.($darkMode ? 'bg-gray-800' : 'bg-gray-50').' '.$size['text']);
    match (true) {
        $disabled => $name->always($darkMode ? 'text-gray-500' : 'text-gray-400'),
        $darkMode => $name->when('ceplokFile', false, 'text-white', 'text-gray-400'),
        default => $name->always('text-gray-900'),
    };

    $input = $attributes
        ->except(['class', 'wire:key', 'id', 'type', 'multiple'])
        ->merge(['aria-describedby' => $hasHelper ? $helperId : null])
        ->class('sr-only');
@endphp

<div {{ $attributes->only(['class', 'wire:key'])->class('w-full') }} x-data="ceplokUpload">
    @if ($hasLabel)
        <label for="{{ $id }}" @class(['mb-2 block text-sm font-bold transition-colors', $darkMode ? 'text-white' : 'text-gray-900'])>{{ $label }}</label>
    @endif

    @if ($attach)
        {{-- Tinggi 230px sudah termasuk garisnya: kotaknya border-box, jadi border 2px menyisakan 226px untuk isi. --}}
        <label
            for="{{ $id }}"
            x-on:dragover.prevent
            x-on:dragenter="ceplokDragEnter()"
            x-on:dragleave="ceplokDrag = false"
            x-on:drop.prevent="ceplokDrop($event)"
            class="{{ $zone->classes() }}"
            @if ($zone->binding()) x-bind:class="{{ $zone->binding() }}" @endif
        >
            <input id="{{ $id }}" type="file" {{ $input }} />

            {{--
                Isinya dimatikan pointer-event-nya supaya dragenter/dragleave hanya
                terpicu oleh kotaknya sendiri; tanpa itu melintasi teks di dalam
                terbaca sebagai keluar dari area dan sorotannya berkedip.
            --}}
            <div class="pointer-events-none flex flex-col items-center">
                {{ svg('ceplok-s-upload', 'size-5 transition-colors '.($disabled ? ($darkMode ? 'text-gray-600' : 'text-gray-300') : 'text-gray-400')) }}
                <span class="mt-2 text-sm transition-colors {{ $muted }}" x-text="ceplokFile ? ceplokFile.name : @js($attachLabel)">{{ $attachLabel }}</span>
                <span class="mt-2 text-xs transition-colors {{ $muted }}">{{ $attachHint }}</span>
            </div>
        </label>
    @else
        <div class="flex items-stretch gap-2">
            <div @class(['flex flex-1 items-stretch overflow-hidden rounded-lg border transition-colors', $darkMode ? 'border-gray-700' : 'border-gray-300', $size['field']])>
                <input id="{{ $id }}" type="file" {{ $input }} />

                <button
                    type="button"
                    x-on:click="ceplokBrowse()"
                    @disabled($disabled)
                    @class([
                        'shrink-0 border-r font-medium text-white transition-colors',
                        $darkMode ? 'border-gray-700' : 'border-gray-300',
                        'focus-visible:outline-2 focus-visible:-outline-offset-2',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        $size['button'],
                        $accent['solid'],
                    ])
                    x-text="ceplokFile ? @js($replaceLabel) : @js($buttonLabel)"
                >{{ $buttonLabel }}</button>

                <p class="{{ $name->classes() }}" @if ($name->binding()) x-bind:class="{{ $name->binding() }}" @endif>
                    <span class="truncate" x-text="ceplokFile ? ceplokFile.name : @js($placeholder)">{{ $placeholder }}</span>
                </p>
            </div>

            <button
                type="button"
                x-show="ceplokFile"
                x-cloak
                x-on:click="ceplokRemove()"
                @disabled($disabled)
                @class([
                    'shrink-0 rounded-lg border px-4 font-medium transition-colors',
                    $darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-700 hover:text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                    $size['text'],
                ])
            >{{ $removeLabel }}</button>
        </div>
    @endif

    {{-- Ukuran berkas hanya pada type default; wadahnya ikut tersembunyi selama tidak ada caption maupun berkas. --}}
    @if ($hasHelper || ! $attach)
        <div class="mt-2 flex flex-col space-y-1" @unless ($hasHelper) x-show="ceplokFile" x-cloak @endunless>
            @unless ($attach)
                <span class="text-xs transition-colors {{ $muted }}" x-show="ceplokFile" x-cloak x-text="ceplokSize"></span>
            @endunless
            @if ($hasHelper)
                <p id="{{ $helperId }}" class="text-xs transition-colors {{ $muted }}">{{ $helperText }}</p>
            @endif
        </div>
    @endif
</div>
