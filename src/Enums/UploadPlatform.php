<?php

namespace CeplokUi\DesignKit\Enums;

/** Ukuran baris pemilih berkas Upload — 44px (default) atau 40px (mobile). Tidak berpengaruh pada type attach. */
enum UploadPlatform: string
{
    case Default = 'default';
    case Mobile = 'mobile';
}
