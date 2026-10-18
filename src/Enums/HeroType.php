<?php

namespace CeplokUi\DesignKit\Enums;

/** Susunan Hero: gambar di kiri, gambar di kanan, atau tanpa gambar dengan isi terpusat. */
enum HeroType: string
{
    case HorizontalImageLeft = 'horizontal-image-left';
    case HorizontalImageRight = 'horizontal-image-right';
    case Centered = 'centered';
}
