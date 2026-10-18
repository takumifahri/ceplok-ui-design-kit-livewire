<?php

namespace CeplokUi\DesignKit\Enums;

/** Ukuran kotak Search — 54px (default), 50px (mobile), atau 37px untuk navbar (compact). */
enum SearchPlatform: string
{
    case Default = 'default';
    case Mobile = 'mobile';
    case Compact = 'compact';
}
