<?php

namespace CeplokUi\DesignKit\Enums;

/** Bentuk Upload — baris tombol + nama berkas (default) atau area seret-lepas (attach). */
enum UploadType: string
{
    case Default = 'default';
    case Attach = 'attach';
}
