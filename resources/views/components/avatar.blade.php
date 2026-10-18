@php
    use CeplokUi\DesignKit\Enums\AvatarSize;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Avatar — lingkaran identitas berisi foto atau inisial.

    Ada `src` berarti foto, tidak ada berarti inisial; foto yang gagal dimuat
    juga jatuh kembali ke inisial (butuh plugin Alpine kit). `alt` adalah nama
    pemiliknya: dipakai sebagai alt foto, dan sebagai nama aksesibilitas saat
    yang tampil hanya inisial. Biarkan kosong bila namanya sudah tertulis tepat
    di sebelah avatar, supaya tidak diumumkan dua kali.

    Inisial lewat prop `initials`, atau slot bawaan bila isinya ikon kecil.
    `dark-mode` hanya mengganti warna lingkaran inisial; foto tidak diubah.
--}}
@props([
    'src' => null,
    'alt' => '',
    'initials' => null,
    'size' => 'default',
    'darkMode' => false,
])

@php
    $size = Prop::oneOf(AvatarSize::class, $size, AvatarSize::Default, 'avatar', 'size');
    $darkMode = Prop::bool($darkMode, false, 'avatar', 'dark-mode');
    $alt = (string) $alt;

    // Teks ikut naik bertingkat — 12px, 16px, lalu 30px — supaya dua huruf
    // mengisi porsi ruang yang kira-kira sama di ketiga ukuran.
    $sizes = [
        'small' => 'size-6 text-xs',
        'default' => 'size-8 text-base',
        'large' => 'size-20 text-3xl',
    ];
    $light = 'bg-gray-100 text-gray-900';
    $dark = 'bg-gray-800 text-gray-100';
    $base = 'grid shrink-0 place-items-center overflow-hidden rounded-full font-normal';
    $hasImage = filled($src);
    $initials = Content::filled($initials) ? $initials : $slot;
@endphp

@if ($hasImage)
    {{--
        Warna lingkaran baru terlihat bila foto gagal, dan pada tampilan gelap
        warnanya ikut berganti saat itu — jadi pada tampilan gelap warnanya
        diatur Alpine sepenuhnya, bukan class statis yang tak bisa dicabut.
    --}}
    <div
        x-data="ceplokAvatar"
        @if ($alt !== '')
            x-bind:role="failed ? 'img' : null"
            x-bind:aria-label="failed ? @js($alt) : null"
        @endif
        @if ($darkMode)
            x-bind:class="failed ? @js($dark) : @js($light)"
        @endif
        {{ $attributes->class([$base, $light => ! $darkMode, $sizes[$size]]) }}
    >
        <img
            x-ref="img"
            x-show="! failed"
            x-on:error="failed = true"
            x-on:load="failed = false"
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="size-full object-cover"
        />
        {{-- Inisial disembunyikan dari pembaca layar: dieja huruf per huruf ia tidak berarti apa-apa. --}}
        <span aria-hidden="true" x-show="failed" x-cloak>{{ $initials }}</span>
    </div>
@else
    <div
        @if ($alt !== '')
            role="img"
            aria-label="{{ $alt }}"
        @endif
        {{ $attributes->class([$base, $darkMode ? $dark : $light, $sizes[$size]]) }}
    >
        {{-- Inisial disembunyikan dari pembaca layar: dieja huruf per huruf ia tidak berarti apa-apa. --}}
        <span aria-hidden="true">{{ $initials }}</span>
    </div>
@endif
