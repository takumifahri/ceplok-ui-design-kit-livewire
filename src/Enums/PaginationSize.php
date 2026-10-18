<?php

namespace CeplokUi\DesignKit\Enums;

/** Ukuran Pagination — kotak 40px (base), 32px (s), atau 32px yang naik ke 40px saat container ≥ 512px (responsive). */
enum PaginationSize: string
{
    case Base = 'base';
    case S = 's';
    case Responsive = 'responsive';
}
