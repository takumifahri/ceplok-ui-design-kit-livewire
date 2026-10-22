<?php

namespace CeplokUi\DesignKit\Enums;

/** Struktur Modal: `default` dengan header, badan, dan footer; `popup` untuk konfirmasi ringkas. */
enum ModalVariant: string
{
    case Default = 'default';
    case Popup = 'popup';
}
