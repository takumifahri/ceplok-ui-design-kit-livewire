<?php

namespace CeplokUi\DesignKit\Enums;

/** Kapan Search mengirim event `search`: saat Enter atau tombol cari (enter), atau setiap isian berubah dengan jeda debounce (change, auto). */
enum SearchTrigger: string
{
    case Enter = 'enter';
    case Change = 'change';
    case Auto = 'auto';
}
