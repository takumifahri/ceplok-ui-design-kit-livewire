{{--
    Icon — pembungkus ikon: menyeragamkan ukuran (bawaan size-5) dan
    menyembunyikannya dari pembaca layar. Isi slot-nya dengan <svg> yang
    memakai currentColor, mis. ikon Flowbite milik kit:

        <x-ceplok::icon class="size-6">{{ svg('ceplok-o-bell') }}</x-ceplok::icon>
--}}
<span {{ $attributes->class('inline-flex size-5 items-center justify-center [&>svg]:size-full')->merge(['aria-hidden' => 'true']) }}>{{ $slot }}</span>
