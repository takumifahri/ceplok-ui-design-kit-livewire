<?php

namespace CeplokUi\DesignKit\Enums;

/** Batas lebar Container di ruang ≥ 640px — 720px, 1126px, 1440px, atau tanpa batas. */
enum ContainerSize: string
{
    case Prose = 'prose';
    case Default = 'default';
    case Wide = 'wide';
    case Full = 'full';
}
